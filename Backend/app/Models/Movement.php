<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'source_id',
        'category_id',
        'type',
        'status',
        'origin',
        'amount',
        'currency',
        'description',
        'operation_date',
        'notes',
        'transfer_group_id',
        'original_movement_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'operation_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the movement.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source of the movement.
     */
    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Get the category of the movement.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the original movement if this is a refund or transfer.
     */
    public function originalMovement()
    {
        return $this->belongsTo(Movement::class, 'original_movement_id');
    }

    /**
     * Get the transfer group if this is part of a transfer.
     */
    public function transferGroup()
    {
        return $this->belongsTo(Movement::class, 'transfer_group_id');
    }

    /**
     * Get the receipt associated with the movement.
     */
    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}