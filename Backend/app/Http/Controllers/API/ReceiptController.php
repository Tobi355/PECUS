<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'file_path' => $path,
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

    public function file(Request $request, string $id)
    {
        $receipt = $request->user()->receipts()->find($id);
        if (! $receipt) {
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        // Handle null or empty file_path
        if (empty($receipt->file_path)) {
            return response()->json(['message' => 'Receipt file not found'], 404);
        }

        $legacyAbsolutePath = str_starts_with($receipt->file_path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $receipt->file_path);
        $fileExists = $legacyAbsolutePath
            ? is_file($receipt->file_path)
            : Storage::disk('public')->exists($receipt->file_path);

        if (! $fileExists) {
            return response()->json(['message' => 'Receipt file not found'], 404);
        }

        return $legacyAbsolutePath
            ? response()->download($receipt->file_path, $receipt->file_name)
            : Storage::disk('public')->download($receipt->file_path, $receipt->file_name);
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

        // Prevent updates on confirmed receipts or those with movements
        if ($receipt->status === 'CONFIRMED' || $receipt->movement_id) {
            return response()->json(['message' => 'A confirmed receipt cannot be changed.'], 422);
        }

        $request->validate([
            'amount' => 'sometimes|required|numeric|gt:0',
            'date' => 'sometimes|required|date',
            'merchant' => 'sometimes|nullable|string|max:255',
            'operation_type' => 'sometimes|required|in:EXPENSE,INCOME',
            'currency' => 'sometimes|required|string|size:3',
            'source_id' => 'sometimes|required|exists:sources,id,user_id,' . $request->user()->id,
            'category_id' => 'sometimes|required|exists:categories,id,user_id,' . $request->user()->id,
            'description' => 'sometimes|nullable|string|max:255',
        ]);

        // Preserve existing extracted data and merge with new data
        $extracted = $receipt->extracted_data ?? [];
        $extracted = array_merge($extracted, $request->only([
            'amount', 'date', 'merchant', 'operation_type', 'currency', 'source_id', 'category_id', 'description',
        ]));

        $receipt->update([
            'extracted_data' => $extracted,
            'status' => collect(['amount', 'date', 'operation_type', 'currency', 'source_id', 'category_id'])
                ->every(fn ($key) => isset($extracted[$key])) ? 'REQUIRES_REVIEW' : 'RECEIVED',
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

        // Check conditions BEFORE locking for efficiency
        if ($receipt->status !== 'REQUIRES_REVIEW' || $receipt->movement_id) {
            return response()->json([
                'message' => 'Complete the manual review before confirming this receipt.',
            ], 422);
        }

        // Validate that we have the required data
        $extracted = $receipt->extracted_data ?? [];
        $requiredFields = ['amount', 'date', 'operation_type', 'currency', 'source_id', 'category_id'];
        $missingFields = array_filter($requiredFields, fn($field) => !isset($extracted[$field]));

        if (!empty($missingFields)) {
            return response()->json([
                'message' => 'Please complete all required fields before confirming.',
                'missing_fields' => array_values($missingFields)
            ], 422);
        }

        // Use database transaction for atomic operation
        return DB::transaction(function () use ($request, $receipt, $extracted) {
            // Lock the receipt for update to prevent race conditions
            $receipt = $request->user()->receipts()->lockForUpdate()->findOrFail($receipt->id);

            // Double-check conditions after acquiring lock
            if ($receipt->movement_id || $receipt->status !== 'REQUIRES_REVIEW') {
                return response()->json(['message' => 'This receipt has already been confirmed or needs manual review.'], 422);
            }

            // Re-extract data from the locked receipt
            $extracted = $receipt->extracted_data ?? [];

            // Validate source and category
            $source = $request->user()->sources()->find($extracted['source_id'] ?? null);
            $category = $request->user()->categories()->find($extracted['category_id'] ?? null);
            if (! $source || ! $category) {
                return response()->json(['message' => 'Select a valid source and category before confirming.'], 422);
            }
            if ($category->type !== $extracted['operation_type']) {
                return response()->json(['message' => 'Select a category matching the movement type.'], 422);
            }

            // Create movement
            $movement = $request->user()->movements()->create([
                'source_id' => $source->id,
                'category_id' => $category->id,
                'type' => $extracted['operation_type'],
                'status' => 'CONFIRMED',
                'origin' => 'RECEIPT',
                'amount' => $extracted['amount'],
                'currency' => $extracted['currency'],
                'description' => $extracted['description'] ?? $extracted['merchant'] ?? null,
                'operation_date' => $extracted['date'],
            ]);

            // Update receipt with movement_id and status
            $receipt->update([
                'movement_id' => $movement->id,
                'status' => 'CONFIRMED',
                'processed_at' => now(),
            ]);

            return response()->json([
                'receipt' => $receipt->load(['movement.source', 'movement.category']),
                'movement' => $movement->load(['source', 'category']),
                'message' => 'Receipt confirmed and movement created successfully',
            ], 201);
        });
    }
}