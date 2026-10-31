<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Goal extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'currency',
        'target_date',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'target_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'progress',
        'remaining',
    ];

    /**
     * Get the user that owns the goal.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0.0;
        }
        return round(($this->current_amount / $this->target_amount) * 100, 2);
    }

    /**
     * Check if goal is completed.
     */
    public function isCompleted(): bool
    {
        return $this->current_amount >= $this->target_amount;
    }

    /**
     * Get remaining amount to reach target.
     */
    public function getRemainingAttribute(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }
}
