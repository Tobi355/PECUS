<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\Movement;
use App\Models\Notification;
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
        $period = $request->query('period', 'all'); // all, week, month, year, custom
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Compute period date ranges using Carbon instances for proper date column handling
        $now = now();
        $periodStart = null;
        $periodEnd = null;

        if ($period !== 'all') {
            if ($period === 'week' && !$startDate && !$endDate) {
                $periodStart = $now->copy()->startOfWeek();
                $periodEnd = $now->copy()->endOfWeek();
            } elseif ($period === 'month' && !$startDate && !$endDate) {
                $periodStart = $now->copy()->startOfMonth();
                $periodEnd = $now->copy()->endOfMonth();
            } elseif ($period === 'year' && !$startDate && !$endDate) {
                $periodStart = $now->copy()->startOfYear();
                $periodEnd = $now->copy()->endOfYear();
            } elseif ($startDate && $endDate) {
                $periodStart = \Carbon\Carbon::parse($startDate)->startOfDay();
                $periodEnd = \Carbon\Carbon::parse($endDate)->endOfDay();
            }
        }

        // Base query for movements
        $movementsQuery = $user->movements()
            ->where('status', 'CONFIRMED');

        // Apply period filtering using whereBetween for reliability
        if ($periodStart && $periodEnd) {
            $movementsQuery->whereBetween('operation_date', [$periodStart, $periodEnd]);
        }

        // Calculate totals for the period
        $income = (float) $movementsQuery->clone()
            ->where('type', 'INCOME')
            ->sum('amount');

        $expenses = (float) $movementsQuery->clone()
            ->where('type', 'EXPENSE')
            ->sum('amount');

        $balance = $income - $expenses;

        // Get sources with their current balance (considering period if specified)
        $sourcesQuery = $user->sources();
        $sources = $sourcesQuery->get()->map(function ($source) use ($user, $periodStart, $periodEnd, $expenses) {
            $sourceMovementsQuery = $source->movements()
                ->where('status', 'CONFIRMED');

            // Apply same period filtering to source movements
            if ($periodStart && $periodEnd) {
                $sourceMovementsQuery->whereBetween('operation_date', [$periodStart, $periodEnd]);
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

        // Calculate available as sum of all source current balances (regardless of period filter)
        $available = $sources->sum('current_balance');

        // Get category distribution for expenses (for the period)
        $categoryDistribution = $movementsQuery->clone()
            ->where('type', 'EXPENSE')
            ->with('category')
            ->get()
            ->groupBy(function ($movement) {
                return $movement->category ? $movement->category->name : 'Sin categoría';
            })
            ->map(function ($movements, $categoryName) use ($expenses) {
                $category = $movements->first()->category;
                return [
                    'category' => $categoryName,
                    'amount' => $movements->sum('amount'),
                    'color' => $category ? $category->color : '#6c757d',
                    'icon' => $category ? $category->icon : 'tag',
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
        if ($periodStart && $periodEnd) {
            $recentMovementsQuery->whereBetween('operation_date', [$periodStart, $periodEnd]);
        }

        $recentMovements = $recentMovementsQuery
            ->orderBy('operation_date', 'desc')
            ->limit(10)
            ->get();

        // Get all goals (not just active)
        $goals = $user->goals()
            ->orderBy('target_date', 'asc')
            ->get()
            ->map(function ($goal) {
                return [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'target_amount' => $goal->target_amount,
                    'current_amount' => $goal->current_amount,
                    'currency' => $goal->currency,
                    'target_date' => $goal->target_date,
                    'status' => $goal->status,
                    'progress' => $goal->progress,
                    'remaining' => $goal->remaining,
                ];
            });

        // Get active budgets for current period
        $now = now();
        $currentWeekStart = $now->copy()->startOfWeek()->format('Y-m-d');
        $currentWeekEnd = $now->copy()->endOfWeek()->format('Y-m-d');
        $currentMonthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = $now->copy()->endOfMonth()->format('Y-m-d');
        $currentYearStart = $now->copy()->startOfYear()->format('Y-m-d');
        $currentYearEnd = $now->copy()->endOfYear()->format('Y-m-d');

        $budgets = $user->budgets()
            ->with('category')
            ->where(function ($query) use ($currentWeekStart, $currentWeekEnd) {
                $query->where('period', 'WEEKLY')
                    ->whereDate('start_date', '<=', $currentWeekStart)
                    ->where(function ($q) use ($currentWeekEnd) {
                        $q->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $currentWeekEnd);
                    });
            })
            ->orWhere(function ($query) use ($now, $currentMonthStart, $currentMonthEnd) {
                $query->where('period', 'MONTHLY')
                    ->whereYear('start_date', $now->year)
                    ->whereMonth('start_date', $now->month)
                    ->where(function ($q) use ($currentMonthEnd) {
                        $q->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $currentMonthEnd);
                    });
            })
            ->orWhere(function ($query) use ($now, $currentYearStart, $currentYearEnd) {
                $query->where('period', 'YEARLY')
                    ->whereYear('start_date', $now->year)
                    ->where(function ($q) use ($currentYearEnd) {
                        $q->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $currentYearEnd);
                    });
            })
            ->get()
            ->map(function ($budget) {
                return [
                    'id' => $budget->id,
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
                ];
            });

        // Get unread notifications count
        $unreadNotificationsCount = $user->notifications()->whereNull('read_at')->count();

        return response()->json([
            'summary' => [
                'available' => $available, // Total available balance (sum of all source balances)
                'income' => $income,
                'expenses' => $expenses,
                'balance' => $balance,
            ],
            'recent_movements' => $recentMovements,
            'sources' => $sources,
            'category_distribution' => $categoryDistribution,
            'goals' => $goals,
            'budgets' => $budgets,
            'unread_notifications_count' => $unreadNotificationsCount,
            'period_info' => [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }
}