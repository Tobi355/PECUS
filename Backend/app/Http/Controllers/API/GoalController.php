<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $goals = $request->user()->goals()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($goal) {
                return $this->formatGoal($goal);
            });

        return response()->json($goals);
    }

    /**
     * Format goal with computed attributes.
     */
    private function formatGoal(Goal $goal): array
    {
        return [
            'id' => $goal->id,
            'user_id' => $goal->user_id,
            'name' => $goal->name,
            'target_amount' => $goal->target_amount,
            'current_amount' => $goal->current_amount,
            'currency' => $goal->currency,
            'target_date' => $goal->target_date,
            'status' => $goal->status,
            'progress' => $goal->progress,
            'remaining' => $goal->remaining,
            'created_at' => $goal->created_at,
            'updated_at' => $goal->updated_at,
            'deleted_at' => $goal->deleted_at,
        ];
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
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3|regex:/^[A-Z]{3}$/',
            'target_date' => 'required|date|after:today',
            'current_amount' => 'nullable|numeric|min:0',
        ]);

        // Use database transaction for atomic operation
        $goal = DB::transaction(function () use ($request) {
            $goal = $request->user()->goals()->create($request->all());
            return $goal;
        });

        return response()->json($this->formatGoal($goal), 201);
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
        $goal = $request->user()->goals()->find($id);

        if (! $goal) {
            return response()->json([
                'message' => 'Goal not found',
            ], 404);
        }

        return response()->json($this->formatGoal($goal));
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
            'name' => 'sometimes|required|string|max:255',
            'target_amount' => 'sometimes|required|numeric|min:0.01',
            'currency' => 'sometimes|required|string|max:3|regex:/^[A-Z]{3}$/',
            'target_date' => 'sometimes|required|date|after:today',
            'current_amount' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|required|in:ACTIVE,COMPLETED,ARCHIVED',
        ]);

        $goal = $request->user()->goals()->find($id);

        if (! $goal) {
            return response()->json([
                'message' => 'Goal not found',
            ], 404);
        }

        // Use database transaction for atomic operation
        $goal = DB::transaction(function () use ($request, $goal) {
            $goal->update($request->all());

            // Auto-update status based on progress
            if ($goal->current_amount >= $goal->target_amount && $goal->status === 'ACTIVE') {
                $goal->update(['status' => 'COMPLETED']);
            }

            return $goal->fresh();
        });

        return response()->json($this->formatGoal($goal));
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
        $goal = $request->user()->goals()->find($id);

        if (! $goal) {
            return response()->json([
                'message' => 'Goal not found',
            ], 404);
        }

        // Use database transaction for atomic operation
        DB::transaction(function () use ($goal) {
            $goal->delete();
        });

        return response()->json([
            'message' => 'Goal deleted successfully',
        ]);
    }
}
