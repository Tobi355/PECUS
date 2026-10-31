<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Movement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $budgets = $request->user()->budgets()
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($budget) {
                return [
                    'id' => $budget->id,
                    'user_id' => $budget->user_id,
                    'category_id' => $budget->category_id,
                    'category' => $budget->category,
                    'amount' => $budget->amount,
                    'currency' => $budget->currency,
                    'period' => $budget->period,
                    'start_date' => $budget->start_date,
                    'end_date' => $budget->end_date,
                    'spent' => $budget->spent,
                    'remaining' => $budget->remaining,
                    'percentage_used' => $budget->percentage_used,
                    'is_exceeded' => $budget->isExceeded(),
                    'is_near_limit' => $budget->isNearLimit(),
                    'created_at' => $budget->created_at,
                    'updated_at' => $budget->updated_at,
                    'deleted_at' => $budget->deleted_at,
                ];
            });

        return response()->json($budgets);
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
            'category_id' => 'required|exists:categories,id,user_id,' . $request->user()->id,
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3|regex:/^[A-Z]{3}$/',
            'period' => 'required|in:WEEKLY,MONTHLY,YEARLY',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Check if category is expense type
        $category = $request->user()->categories()->find($request->category_id);
        if (! $category || $category->type !== 'EXPENSE') {
            return response()->json([
                'message' => 'Budget category must be an expense category.',
            ], 422);
        }

        // Check for existing active budget for same category and period
        $existingBudget = $request->user()->budgets()
            ->where('category_id', $request->category_id)
            ->where('period', $request->period)
            ->whereNull('deleted_at')
            ->first();

        if ($existingBudget) {
            return response()->json([
                'message' => 'A budget for this category and period already exists.',
            ], 422);
        }

        // Use database transaction for atomic operation
        $budget = DB::transaction(function () use ($request) {
            $budget = $request->user()->budgets()->create($request->all());
            return $budget->load('category');
        });

        return response()->json([
            'id' => $budget->id,
            'user_id' => $budget->user_id,
            'category_id' => $budget->category_id,
            'category' => $budget->category,
            'amount' => $budget->amount,
            'currency' => $budget->currency,
            'period' => $budget->period,
            'start_date' => $budget->start_date,
            'end_date' => $budget->end_date,
            'spent' => $budget->spent,
            'remaining' => $budget->remaining,
            'percentage_used' => $budget->percentage_used,
            'is_exceeded' => $budget->isExceeded(),
            'is_near_limit' => $budget->isNearLimit(),
            'created_at' => $budget->created_at,
            'updated_at' => $budget->updated_at,
            'deleted_at' => $budget->deleted_at,
        ], 201);
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
        $budget = $request->user()->budgets()->with('category')->find($id);

        if (! $budget) {
            return response()->json([
                'message' => 'Budget not found',
            ], 404);
        }

        return response()->json([
            'id' => $budget->id,
            'user_id' => $budget->user_id,
            'category_id' => $budget->category_id,
            'category' => $budget->category,
            'amount' => $budget->amount,
            'currency' => $budget->currency,
            'period' => $budget->period,
            'start_date' => $budget->start_date,
            'end_date' => $budget->end_date,
            'spent' => $budget->spent,
            'remaining' => $budget->remaining,
            'percentage_used' => $budget->percentage_used,
            'is_exceeded' => $budget->isExceeded(),
            'is_near_limit' => $budget->isNearLimit(),
            'created_at' => $budget->created_at,
            'updated_at' => $budget->updated_at,
            'deleted_at' => $budget->deleted_at,
        ]);
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
            'category_id' => 'sometimes|required|exists:categories,id,user_id,' . $request->user()->id,
            'amount' => 'sometimes|required|numeric|min:0.01',
            'currency' => 'sometimes|required|string|max:3|regex:/^[A-Z]{3}$/',
            'period' => 'sometimes|required|in:WEEKLY,MONTHLY,YEARLY',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $budget = $request->user()->budgets()->find($id);

        if (! $budget) {
            return response()->json([
                'message' => 'Budget not found',
            ], 404);
        }

        // If changing category, validate it's an expense category
        if ($request->has('category_id')) {
            $category = $request->user()->categories()->find($request->category_id);
            if (! $category || $category->type !== 'EXPENSE') {
                return response()->json([
                    'message' => 'Budget category must be an expense category.',
                ], 422);
            }
        }

        // Check for duplicate budget
        if ($request->has('category_id') || $request->has('period')) {
            $categoryId = $request->category_id ?? $budget->category_id;
            $period = $request->period ?? $budget->period;

            $existingBudget = $request->user()->budgets()
                ->where('category_id', $categoryId)
                ->where('period', $period)
                ->where('id', '!=', $budget->id)
                ->whereNull('deleted_at')
                ->first();

            if ($existingBudget) {
                return response()->json([
                    'message' => 'A budget for this category and period already exists.',
                ], 422);
            }
        }

        // Use database transaction for atomic operation
        $budget = DB::transaction(function () use ($request, $budget) {
            $budget->update($request->all());
            return $budget->load('category');
        });

        return response()->json([
            'id' => $budget->id,
            'user_id' => $budget->user_id,
            'category_id' => $budget->category_id,
            'category' => $budget->category,
            'amount' => $budget->amount,
            'currency' => $budget->currency,
            'period' => $budget->period,
            'start_date' => $budget->start_date,
            'end_date' => $budget->end_date,
            'spent' => $budget->spent,
            'remaining' => $budget->remaining,
            'percentage_used' => $budget->percentage_used,
            'is_exceeded' => $budget->isExceeded(),
            'is_near_limit' => $budget->isNearLimit(),
            'created_at' => $budget->created_at,
            'updated_at' => $budget->updated_at,
            'deleted_at' => $budget->deleted_at,
        ]);
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
        $budget = $request->user()->budgets()->find($id);

        if (! $budget) {
            return response()->json([
                'message' => 'Budget not found',
            ], 404);
        }

        // Use database transaction for atomic operation
        DB::transaction(function () use ($budget) {
            $budget->delete();
        });

        return response()->json([
            'message' => 'Budget deleted successfully',
        ]);
    }
}