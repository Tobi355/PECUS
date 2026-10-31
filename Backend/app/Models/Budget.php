<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Budget extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'currency',
        'period',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the budget.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for the budget.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get spent amount for the budget's period.
     */
    public function getSpentAttribute(): float
    {
        $query = Movement::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'EXPENSE')
            ->where('status', 'CONFIRMED');

        // Determine the period range based on the budget's start_date and period type
        // Use start_date as the reference point for the period calculation
        $referenceDate = $this->start_date ? \Carbon\Carbon::parse($this->start_date) : now();

        if ($this->period === 'WEEKLY') {
            $periodStart = $referenceDate->copy()->startOfWeek();
            $periodEnd = $referenceDate->copy()->endOfWeek();
            $query->whereBetween('operation_date', [$periodStart, $periodEnd]);
        } elseif ($this->period === 'MONTHLY') {
            $periodStart = $referenceDate->copy()->startOfMonth();
            $periodEnd = $referenceDate->copy()->endOfMonth();
            $query->whereBetween('operation_date', [$periodStart, $periodEnd]);
        } elseif ($this->period === 'YEARLY') {
            $periodStart = $referenceDate->copy()->startOfYear();
            $periodEnd = $referenceDate->copy()->endOfYear();
            $query->whereBetween('operation_date', [$periodStart, $periodEnd]);
        }

        // Also respect end_date if set (as an upper bound)
        if ($this->end_date) {
            $query->whereDate('operation_date', '<=', $this->end_date);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get remaining amount.
     */
    public function getRemainingAttribute(): float
    {
        return max(0, $this->amount - $this->spent);
    }

    /**
     * Get percentage used.
     */
    public function getPercentageUsedAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0.0;
        }
        return round(($this->spent / $this->amount) * 100, 2);
    }

    /**
     * Check if budget is exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->spent > $this->amount;
    }

    /**
     * Check if budget is near limit (80%).
     */
    public function isNearLimit(): bool
    {
        return $this->percentage_used >= 80;
    }
}
