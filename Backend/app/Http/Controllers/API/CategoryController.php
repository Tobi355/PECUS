<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $categories = $request->user()->categories()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($categories);
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
            'type' => 'required|in:EXPENSE,INCOME',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
        ]);

        // Use database transaction for atomic operation
        $category = DB::transaction(function () use ($request) {
            $category = $request->user()->categories()->create($request->all());
            return $category;
        });

        return response()->json($category, 201);
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
        $category = $request->user()->categories()->find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json($category);
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
            'type' => 'sometimes|required|in:EXPENSE,INCOME',
            'icon' => 'sometimes|nullable|string|max:50',
            'color' => 'sometimes|nullable|string|max:20',
            'is_default' => 'sometimes|boolean',
        ]);

        $category = $request->user()->categories()->find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        // Prevent editing of default categories if not admin?
        // For now, we'll allow editing but note in comments
        // In a real app, you might want to restrict this based on roles

        // Use database transaction for atomic operation
        $category = DB::transaction(function () use ($request, $category) {
            $category->update($request->all());
            return $category;
        });

        return response()->json($category);
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
        $category = $request->user()->categories()->find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        // Check if category has active movements (excluding soft deleted)
        $activeMovementsCount = $category->movements()
            ->whereNull('deleted_at')
            ->count();

        if ($activeMovementsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete category that has active movements. Please move or delete movements first.',
            ], 422);
        }

        // Prevent deletion of default categories if they're the only ones of their type
        // This is a business rule - you might want to allow deleting defaults if user has created others
        if ($category->is_default) {
            $sameTypeCount = $request->user()->categories()
                ->where('type', $category->type)
                ->where('id', '!=', $category->id)
                ->whereNull('deleted_at')
                ->count();

            // If this is the only category of its type and it's default, prevent deletion
            // unless user has created other categories of the same type
            if ($sameTypeCount == 0) {
                return response()->json([
                    'message' => 'Cannot delete the default category. Please create another category of this type first.',
                ], 422);
            }
        }

        // Use database transaction for atomic operation
        DB::transaction(function () use ($category) {
            $category->delete();
        });

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}