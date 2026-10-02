<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Movement;
use App\Models\Source;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ProcessReceiptService;

class ReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $receipts = $request->user()->receipts()
            ->with(['movement.source', 'movement.category'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($receipts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        // Validate file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $extension = strtolower($request->file->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions)) {
            return response()->json([
                'message' => 'Invalid file type. Allowed types: JPG, JPEG, PNG, PDF.',
            ], 422);
        }

        // Store the file
        $path = $request->file->store('receipts', 'public');

        // Create receipt record
        $receipt = $request->user()->receipts()->create([
            'file_path' => Storage::disk('public')->path($path),
            'file_name' => $request->file->getClientOriginalName(),
            'mime_type' => $request->file->getClientMimeType(),
            'status' => 'RECEIVED',
        ]);

        return response()->json($receipt, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id)
    {
        $receipt = $request->user()->receipts()
            ->with(['movement.source', 'movement.category'])
            ->find($id);

        if (! $receipt) {
            return response()->json([
                'message' => 'Receipt not found',
            ], 404);
        }

        return response()->json($receipt);
    }

    /**
     * Update the specified resource in storage (review).
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $receipt = $request->user()->receipts()->find($id);

        if (! $receipt) {
            return response()->json([
                'message' => 'Receipt not found',
            ], 404);
        }

        // Validate input for review
        $request->validate([
            'amount' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
            'merchant' => 'sometimes|required|string|max:255',
            'operation_type' => 'sometimes|required|in:EXPENSE,INCOME',
            'currency' => 'sometimes|required|string|max:3',
            'source_id' => 'sometimes|required|exists:sources,id,user_id,' . $request->user()->id,
            'category_id' => 'sometimes|required|exists:categories,id,user_id,' . $request->user()->id,
            'description' => 'sometimes|string|max:255',
        ]);

        // Update extracted_data with the reviewed data
        $extracted = $receipt->extracted_data ?? [];
        $extracted = array_merge($extracted, [
            'amount' => $request->amount ?? ($extracted['amount'] ?? null),
            'date' => $request->date ?? ($extracted['date'] ?? null),
            'merchant' => $request->merchant ?? ($extracted['merchant'] ?? null),
            'operation_type' => $request->operation_type ?? ($extracted['operation_type'] ?? null),
            'currency' => $request->currency ?? ($extracted['currency'] ?? null),
            'source_id' => $request->source_id ?? ($extracted['source_id'] ?? null),
            'category_id' => $request->category_id ?? ($extracted['category_id'] ?? null),
            'description' => $request->description ?? ($extracted['description'] ?? null),
        ]);

        $receipt->update([
            'extracted_data' => $extracted,
        ]);

        return response()->json($receipt);
    }

    /**
     * Process the receipt (mock processing).
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request, string $id)
    {
        $receipt = $request->user()->receipts()->find($id);

        if (! $receipt) {
            return response()->json([
                'message' => 'Receipt not found',
            ], 404);
        }

        if ($receipt->status !== 'RECEIVED') {
            return response()->json([
                'message' => 'Receipt cannot be processed in its current state.',
            ], 422);
        }

        // Update status to processing
        $receipt->update([
            'status' => 'PROCESSING',
        ]);

        // Mock processing: generate plausible data
        // In a real app, this would call an OCR service or AI model.
        // For demo, we'll return fixed data or based on filename.
        $mockData = $this->generateMockData($receipt->file_name);

        // Update receipt with extracted data
        $receipt->update([
            'status' => 'PROCESSED',
            'confidence' => 'HIGH',
            'extracted_data' => $mockData,
            'processed_at' => now(),
        ]);

        return response()->json($receipt);
    }

    /**
     * Confirm the receipt and create a movement.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request, string $id)
    {
        $receipt = $request->user()->receipts()->find($id);

        if (! $receipt) {
            return response()->json([
                'message' => 'Receipt not found',
            ], 404);
        }

        if ($receipt->status !== 'PROCESSED') {
            return response()->json([
                'message' => 'Receipt must be processed before confirmation.',
            ], 422);
        }

        $extracted = $receipt->extracted_data;

        // Validate required fields
        if (! isset($extracted['amount']) || ! isset($extracted['date']) || ! isset($extracted['merchant'])) {
            return response()->json([
                'message' => 'Missing required data for confirmation.',
            ], 422);
        }

        // Begin transaction
        return \DB::transaction(function () use ($request, $receipt, $extracted) {
            // Find or create source based on extracted data
            $source = null;
            if (isset($extracted['source_id'])) {
                $source = $request->user()->sources()->find($extracted['source_id']);
            }
            if (! $source) {
                // Default to first source or create a generic one? For demo, we'll use the first source.
                $source = $request->user()->sources()->first();
                if (! $source) {
                    // Create a default source if none exists
                    $source = $request->user()->sources()->create([
                        'name' => 'Default Source',
                        'type' => 'WALLET',
                        'currency' => 'ARS',
                        'initial_balance' => 0.00,
                        'status' => 'ACTIVE',
                    ]);
                }
            }

            // Find or create category based on extracted data
            $category = null;
            if (isset($extracted['category_id'])) {
                $category = $request->user()->categories()->find($extracted['category_id']);
            }
            if (! $category) {
                // Default to first category or create a generic one? For demo, we'll use the first category.
                $category = $request->user()->categories()->first();
                if (! $category) {
                    // Create a default category if none exists
                    $category = $request->user()->categories()->create([
                        'name' => 'Default Category',
                        'type' => 'EXPENSE',
                        'icon' => null,
                        'color' => null,
                        'is_default' => true,
                    ]);
                }
            }

            // Create movement
            $movement = $request->user()->movements()->create([
                'source_id' => $source->id,
                'category_id' => $category->id,
                'type' => $extracted['operation_type'] ?? 'EXPENSE',
                'status' => 'CONFIRMED',
                'origin' => 'RECEIPT',
                'amount' => $extracted['amount'],
                'currency' => $extracted['currency'] ?? 'ARS',
                'description' => $extracted['description'] ?? $extracted['merchant'],
                'operation_date' => $extracted['date'],
            ]);

            // Update receipt with movement_id and status
            $receipt->update([
                'movement_id' => $movement->id,
                'status' => 'CONFIRMED',
            ]);

            return response()->json([
                'receipt' => $receipt->load(['movement.source', 'movement.category']),
                'movement' => $movement->load(['source', 'category']),
                'message' => 'Receipt confirmed and movement created successfully',
            ], 201);
        });
    }

    /**
     * Generate mock data for receipt processing.
     *
     * @param  string  $filename
     * @return array
     */
    private function generateMockData($filename)
    {
        // For demo, we can return fixed data or vary based on filename.
        // Let's return some plausible data.
        return [
            'amount' => 12500,
            'date' => '2026-10-02',
            'time' => '13:42',
            'merchant' => 'Supermercado Ejemplo',
            'operation_type' => 'EXPENSE',
            'currency' => 'ARS',
            'source' => 'Mercado Pago',
            'category' => 'Alimentación',
            'description' => 'Compra de supermercado',
        ];
    }
}