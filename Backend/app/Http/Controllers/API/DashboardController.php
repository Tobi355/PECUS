<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Get period filter from query params (optional)
        $period = $request->query('period', 'all'); // all, month, year, custom
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Base query for movements
        $movementsQuery = $user->movements()
            ->where('status', 'CONFIRMED');

        // Apply period filtering
        if ($period !== 'all') {
            if ($period === 'month' && !$startDate && !$endDate) {
                // Current month
                $movementsQuery->whereYear('operation_date', now()->year)
                    ->whereMonth('operation_date', now()->month);
            } elseif ($period === 'year' && !$startDate && !$endDate) {
                // Current year
                $movementsQuery->whereYear('operation_date', now()->year);
            } elseif ($startDate && $endDate) {
                // Custom date range
                $movementsQuery->whereBetween('operation_date', [$startDate, $endDate]);
            }
        }

        // Calculate totals for the period
        $income = $movementsQuery->clone()
            ->where('type', 'INCOME')
            ->sum('amount');

        $expenses = $movementsQuery->clone()
            ->where('type', 'EXPENSE')
            ->sum('amount');

        $balance = $income - $expenses;

        // Get sources with their current balance (considering period if specified)
        $sourcesQuery = $user->sources();
        $sources = $sourcesQuery->get()->map(function ($source) use ($user, $period, $startDate, $endDate) {
            $sourceMovementsQuery = $source->movements()
                ->where('status', 'CONFIRMED');

            // Apply same period filtering to source movements
            if ($period !== 'all') {
                if ($period === 'month' && !$startDate && !$endDate) {
                    $sourceMovementsQuery->whereYear('operation_date', now()->year)
                        ->whereMonth('operation_date', now()->month);
                } elseif ($period === 'year' && !$startDate && !$endDate) {
                    $sourceMovementsQuery->whereYear('operation_date', now()->year);
                } elseif ($startDate && $endDate) {
                    $sourceMovementsQuery->whereBetween('operation_date', [$startDate, $endDate]);
                }
            }

            $movements = $sourceMovementsQuery->get();

            $sourceBalance = $source->initial_balance +
                $movements->sum(function ($movement) {
                    // For income, add amount; for expense, subtract amount; for transfers, zero
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

        // Get category distribution for expenses (for the period)
        $categoryDistribution = $movementsQuery->clone()
            ->where('type', 'EXPENSE')
            ->with('category')
            ->get()
            ->groupBy(function ($movement) {
                return $movement->category ? $movement->category->name : 'Sin categoría';
            })
            ->map(function ($movements, $categoryName) {
                return [
                    'category' => $categoryName,
                    'amount' => $movements->sum('amount'),
                    'percentage' => $expenses > 0 ? round(($movements->sum('amount') / $expenses) * 100, 2) : 0
                ];
            })
            ->values()
            ->all();

        // Get recent movements (for the period)
        $recentMovementsQuery = $user->movements()
            ->with(['source', 'category'])
            ->where('status', 'CONFIRMED');

        // Apply same period filtering to recent movements
        if ($period !== 'all') {
            if ($period === 'month' && !$startDate && !$endDate) {
                $recentMovementsQuery->whereYear('operation_date', now()->year)
                    ->whereMonth('operation_date', now()->month);
            } elseif ($period === 'year' && !$startDate && !$endDate) {
                $recentMovementsQuery->whereYear('operation_date', now()->year);
            } elseif ($startDate && $endDate) {
                $recentMovementsQuery->whereBetween('operation_date', [$startDate, $endDate]);
            }
        }

        $recentMovements = $recentMovementsQuery
            ->orderBy('operation_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'available' => $balance, // Total available balance (income - expenses)
                'income' => $income,
                'expenses' => $expenses,
                'balance' => $balance,
            ],
            'recent_movements' => $recentMovements,
            'sources' => $sources,
            'category_distribution' => $categoryDistribution,
            'period_info' => [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }
}