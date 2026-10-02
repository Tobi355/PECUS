<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SourceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $sources = $request->user()->sources()->get();

        return response()->json($sources);
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
            'type' => 'required|in:BANK,CREDIT_CARD,WALLET,CASH,OTHER',
            'currency' => 'required|string|max:3',
            'initial_balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $source = $request->user()->sources()->create($request->all());

        return response()->json($source, 201);
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
        $source = $request->user()->sources()->find($id);

        if (! $source) {
            return response()->json([
                'message' => 'Source not found',
            ], 404);
        }

        return response()->json($source);
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
            'type' => 'sometimes|required|in:BANK,CREDIT_CARD,WALLET,CASH,OTHER',
            'currency' => 'sometimes|required|string|max:3',
            'initial_balance' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|required|in:ACTIVE,INACTIVE',
        ]);

        $source = $request->user()->sources()->find($id);

        if (! $source) {
            return response()->json([
                'message' => 'Source not found',
            ], 404);
        }

        $source->update($request->all());

        return response()->json($source);
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
        $source = $request->user()->sources()->find($id);

        if (! $source) {
            return response()->json([
                'message' => 'Source not found',
            ], 404);
        }

        $source->delete();

        return response()->json([
            'message' => 'Source deleted successfully',
        ]);
    }
}