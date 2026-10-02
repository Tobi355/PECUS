<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Source;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard data for the authenticated user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Calculate totals
        $income = $user->movements()
            ->where('type', 'INCOME')
            ->where('status', 'CONFIRMED')
            ->sum('amount');

        $expenses = $user->movements()
            ->where('type', 'EXPENSE')
            ->where('status', 'CONFIRMED')
            ->sum('amount');

        // For internal transfers, we don't count them as income or expense
        // Already filtered by type.

        $balance = $income - $expenses;

        // Get sources with their current balance (initial_balance + sum of movement amounts)
        $sources = $user->sources()->get()->map(function ($source) {
            $movements = $source->movements()
                ->where('status', 'CONFIRMED')
                ->get();

            $sourceBalance = $source->initial_balance +
                $movements->sum(function ($movement) {
                    // For income, add amount; for expense, subtract amount; for transfers, zero?
                    // Actually, internal transfers should not affect the source balance?
                    // But spec says: initial_balance + income - expense = current balance.
                    // So we treat income as positive, expense as negative, transfers as zero.
                    if ($movement->type === 'INCOME') {
                        return $movement->amount;
                    } elseif ($movement->type === 'EXPENSE') {
                        return -$movement->amount;
                    } else {
                        // Transfer types: no change to balance
                        return 0.0;
                    }
                });

            $source->current_balance = $sourceBalance;
            return $source;
        });

        // Get recent movements
        $recentMovements = $user->movements()
            ->with(['source', 'category'])
            ->where('status', 'CONFIRMED')
            ->orderBy('operation_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'available' => $balance, // dinero disponible? We'll use balance as available.
                'income' => $income,
                'expenses' => $expenses,
                'balance' => $balance,
            ],
            'recent_movements' => $recentMovements,
            'sources' => $sources,
        ]);
    }
}