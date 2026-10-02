<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Illuminate\Http\Request;
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
        $movements = $request->user()->movements()
            ->with(['source', 'category'])
            ->orderBy('operation_date', 'desc')
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
        $request->validate([
            'source_id' => 'sometimes|required|exists:sources,id,user_id,' . $request->user()->id,
            'category_id' => 'sometimes|required|exists:categories,id,user_id,' . $request->user()->id,
            'type' => 'required|in:EXPENSE,INCOME,INTERNAL_TRANSFER,EXTERNAL_TRANSFER,REFUND',
            'status' => 'sometimes|required|in:PENDING,CONFIRMED',
            'origin' => 'required|in:MANUAL,RECEIPT,SHARED,CSV,API,AUTOMATIC',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'description' => 'sometimes|string|max:255',
            'operation_date' => 'required|date',
            'notes' => 'sometimes|string',
            'transfer_group_id' => 'sometimes|exists:movements,id,user_id,' . $request->user()->id,
            'original_movement_id' => 'sometimes|exists:movements,id,user_id,' . $request->user()->id,
        ]);

        $movement = $request->user()->movements()->create($request->all());

        return response()->json($movement->load(['source', 'category']), 201);
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
        $request->validate([
            'source_id' => 'sometimes|required|exists:sources,id,user_id,' . $request->user()->id,
            'category_id' => 'sometimes|required|exists:categories,id,user_id,' . $request->user()->id,
            'type' => 'sometimes|required|in:EXPENSE,INCOME,INTERNAL_TRANSFER,EXTERNAL_TRANSFER,REFUND',
            'status' => 'sometimes|required|in:PENDING,CONFIRMED',
            'origin' => 'sometimes|required|in:MANUAL,RECEIPT,SHARED,CSV,API,AUTOMATIC',
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|max:3',
            'description' => 'sometimes|string|max:255',
            'operation_date' => 'sometimes|date',
            'notes' => 'sometimes|string',
            'transfer_group_id' => 'sometimes|exists:movements,id,user_id,' . $request->user()->id,
            'original_movement_id' => 'sometimes|exists:movements,id,user_id,' . $request->user()->id,
        ]);

        $movement = $request->user()->movements()->find($id);

        if (! $movement) {
            return response()->json([
                'message' => 'Movement not found',
            ], 404);
        }

        $movement->update($request->all());

        return response()->json($movement->load(['source', 'category']));
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

        $movement->delete();

        return response()->json([
            'message' => 'Movement deleted successfully',
        ]);
    }
}