<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Base query for movements
        $movementsQuery = $user->movements()
            ->with(['source', 'category']);

        // Apply filters
        $type = $request->query('type');
        if ($type) {
            $movementsQuery->where('type', $type);
        }

        $status = $request->query('status');
        if ($status) {
            $movementsQuery->where('status', $status);
        }

        $sourceId = $request->query('source_id');
        if ($sourceId) {
            $movementsQuery->where('source_id', $sourceId);
        }

        $categoryId = $request->query('category_id');
        if ($categoryId) {
            $movementsQuery->where('category_id', $categoryId);
        }

        // Date range filtering
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        if ($startDate && $endDate) {
            $movementsQuery->whereBetween('operation_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $movementsQuery->whereDate('operation_date', '>=', $startDate);
        } elseif ($endDate) {
            $movementsQuery->whereDate('operation_date', '<=', $endDate);
        }

        // Order by operation_date desc (most recent first)
        $movements = $movementsQuery->orderBy('operation_date', 'desc')
            ->get();

        return response()->json($movements);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $this->validateMovementData($request);

        // Use database transaction for atomic operation
        DB::beginTransaction();
        try {
            $movement = $request->user()->movements()->create($validatedData);

            // If this is an internal transfer, create the destination movement
            if ($movement->type === 'INTERNAL_TRANSFER' && $movement->destination_source_id) {
                if (! $movement->transfer_group_id) {
                    $movement->transfer_group_id = $movement->id;
                    $movement->save();
                }

                // Create the destination movement (income in the destination account)
                $destinationMovement = $request->user()->movements()->create([
                    'source_id' => $movement->destination_source_id,
                    'type' => 'INTERNAL_TRANSFER',
                    'status' => $movement->status,
                    'origin' => $movement->origin,
                    'amount' => $movement->amount,
                    'currency' => $movement->currency,
                    'description' => $movement->description,
                    'operation_date' => $movement->operation_date,
                    'notes' => $movement->notes,
                    'transfer_group_id' => $movement->transfer_group_id,
                    'destination_source_id' => null, // No further destination
                    'category_id' => null, // Transfers don't have categories
                ]);
            }
            // If this is an external transfer, ensure transfer_group_id is set properly
            elseif ($movement->type === 'EXTERNAL_TRANSFER') {
                if (! $movement->transfer_group_id) {
                    // Set transfer_group_id to its own ID if not provided
                    $movement->transfer_group_id = $movement->id;
                    $movement->save();
                }
            }

            DB::commit();

            return response()->json($movement->load(['source', 'category']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
        $movement = $request->user()->movements()
            ->with(['source', 'category'])
            ->find($id);

        if (! $movement) {
            return response()->json([
                'message' => 'Movement not found',
            ], 404);
        }

        return response()->json($movement);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $this->validateMovementData($request, $id);

        // Use database transaction for atomic operation
        DB::beginTransaction();
        try {
            $movement = $request->user()->movements()->find($id);

            if (! $movement) {
                return response()->json([
                    'message' => 'Movement not found',
                ], 404);
            }

            $wasInternalTransfer = $movement->type === 'INTERNAL_TRANSFER';
            $oldTransferGroupId = $movement->transfer_group_id;
            $oldDestinationSourceId = $movement->destination_source_id;

            $movement->update($validatedData);

            // Handle internal transfer destination movement
            if ($movement->type === 'INTERNAL_TRANSFER' && $movement->destination_source_id) {
                if (! $movement->transfer_group_id) {
                    $movement->transfer_group_id = $movement->id;
                    $movement->save();
                }

                // Find or create the destination movement
                $destinationMovement = $request->user()->movements()
                    ->where('transfer_group_id', $movement->transfer_group_id)
                    ->where('id', '!=', $movement->id)
                    ->first();

                if ($destinationMovement) {
                    // Update existing destination movement
                    $destinationMovement->update([
                        'source_id' => $movement->destination_source_id,
                        'amount' => $movement->amount,
                        'currency' => $movement->currency,
                        'description' => $movement->description,
                        'operation_date' => $movement->operation_date,
                        'notes' => $movement->notes,
                        'status' => $movement->status,
                    ]);
                } else {
                    // Create new destination movement
                    $request->user()->movements()->create([
                        'source_id' => $movement->destination_source_id,
                        'type' => 'INTERNAL_TRANSFER',
                        'status' => $movement->status,
                        'origin' => $movement->origin,
                        'amount' => $movement->amount,
                        'currency' => $movement->currency,
                        'description' => $movement->description,
                        'operation_date' => $movement->operation_date,
                        'notes' => $movement->notes,
                        'transfer_group_id' => $movement->transfer_group_id,
                        'category_id' => null,
                    ]);
                }
            }
            // If changing from internal transfer to something else, delete the destination movement
            elseif ($wasInternalTransfer && $movement->type !== 'INTERNAL_TRANSFER') {
                $request->user()->movements()
                    ->where('transfer_group_id', $oldTransferGroupId)
                    ->where('id', '!=', $movement->id)
                    ->delete();
            }
            // If destination source changed, update the destination movement
            elseif ($wasInternalTransfer && $movement->type === 'INTERNAL_TRANSFER' && $movement->destination_source_id != $oldDestinationSourceId) {
                $request->user()->movements()
                    ->where('transfer_group_id', $movement->transfer_group_id)
                    ->where('id', '!=', $movement->id)
                    ->update(['source_id' => $movement->destination_source_id]);
            }
            // If this is an external transfer, ensure transfer_group_id is set properly
            elseif ($movement->type === 'EXTERNAL_TRANSFER') {
                if (! $movement->transfer_group_id) {
                    $movement->transfer_group_id = $movement->id;
                    $movement->save();
                }
            }

            DB::commit();

            return response()->json($movement->load(['source', 'category']));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $id)
    {
        $movement = $request->user()->movements()->find($id);

        if (! $movement) {
            return response()->json([
                'message' => 'Movement not found',
            ], 404);
        }

        $transferGroupId = $movement->transfer_group_id;
        $isInternalTransfer = $movement->type === 'INTERNAL_TRANSFER';

        // Use database transaction for atomic operation
        DB::beginTransaction();
        try {
            $movement->delete();

            // If this was an internal transfer, also delete the destination movement
            if ($isInternalTransfer && $transferGroupId) {
                $request->user()->movements()
                    ->where('transfer_group_id', $transferGroupId)
                    ->delete();
            }

            DB::commit();

            return response()->json([
                'message' => 'Movement deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate movement data with conditional rules for transfers.
     *
     * @param  Request  $request
     * @param  string|null $id (for update operations)
     * @return array
     */
    private function validateMovementData(Request $request, $id = null)
    {
        $rules = [
            'source_id' => 'sometimes|required|exists:sources,id,user_id,' . $request->user()->id,
            'destination_source_id' => 'sometimes|exists:sources,id,user_id,' . $request->user()->id,
            'category_id' => 'sometimes|required|exists:categories,id,user_id,' . $request->user()->id,
            'type' => ['sometimes', 'required', 'in:EXPENSE,INCOME,INTERNAL_TRANSFER,EXTERNAL_TRANSFER,REFUND'],
            'status' => ['sometimes', 'required', 'in:PENDING,CONFIRMED'],
            'origin' => ['sometimes', 'required', 'in:MANUAL,RECEIPT,SHARED,CSV,API,AUTOMATIC'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999.99'],
            'currency' => ['sometimes', 'required', 'string', 'max:3', 'regex:/^[A-Z]{3}$/'],
            'description' => 'sometimes|string|max:255',
            'operation_date' => 'sometimes|date',
            'notes' => 'sometimes|string',
            'transfer_group_id' => 'sometimes|exists:movements,id,user_id,' . $request->user()->id,
            'original_movement_id' => 'sometimes|exists:movements,id,user_id,' . $request->user()->id,
        ];

        // Add conditional validation for transfers
        if ($request->has('type')) {
            $type = $request->input('type');

            if ($type === 'INTERNAL_TRANSFER' || $type === 'EXTERNAL_TRANSFER') {
                // For transfers, category_id should not be required
                $rules['category_id'] = 'sometimes';

                // For internal transfers, destination_source_id is required
                if ($type === 'INTERNAL_TRANSFER') {
                    $rules['destination_source_id'] = 'required|exists:sources,id,user_id,' . $request->user()->id;
                    $rules['transfer_group_id'] = 'sometimes|exists:movements,id,user_id,' . $request->user()->id;
                }
            } elseif ($type === 'REFUND') {
                // For refunds, original_movement_id is required
                $rules['original_movement_id'] = 'required|exists:movements,id,user_id,' . $request->user()->id;
                // For refunds, transfer_group_id should not be required
                $rules['transfer_group_id'] = 'sometimes';
            } else {
                // For regular expenses/income, category_id is required
                $rules['category_id'] = 'required|exists:categories,id,user_id,' . $request->user()->id;
                // Transfer fields should not be required
                $rules['transfer_group_id'] = 'sometimes';
                $rules['original_movement_id'] = 'sometimes';
            }
        }

        // Custom validation for transfer consistency
        $customMessages = [];
        $customAttributes = [];

        $validator = \Illuminate\Support\Facades\Validator::make(
            $request->all(),
            $rules,
            $customMessages,
            $customAttributes
        );

        $validator->after(function ($validator) use ($request) {
            if ($request->has('type') && in_array($request->input('type'), ['INTERNAL_TRANSFER', 'EXTERNAL_TRANSFER'])) {
                if (! $request->has('transfer_group_id') && ! $request->has('original_movement_id')) {
                    $validator->errors()->add('transfer_group_id', 'Transfer group ID is required for transfers.');
                }
            }
            if ($request->has('type') && $request->input('type') === 'REFUND') {
                if (! $request->has('original_movement_id')) {
                    $validator->errors()->add('original_movement_id', 'Original movement ID is required for refunds.');
                }
            }
            if ($request->has('amount') && $request->input('amount') <= 0) {
                $validator->errors()->add('amount', 'Amount must be greater than zero.');
            }
            // For internal transfers, ensure destination source is different from origin source
            if ($request->has('type') && $request->input('type') === 'INTERNAL_TRANSFER') {
                if ($request->has('source_id') && $request->has('destination_source_id')) {
                    if ($request->input('source_id') == $request->input('destination_source_id')) {
                        $validator->errors()->add('destination_source_id', 'La fuente destino debe ser diferente a la fuente origen.');
                    }
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}