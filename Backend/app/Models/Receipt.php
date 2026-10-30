<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Receipt extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'file_path',
        'file_name',
        'mime_type',
        'status',
        'extracted_data',
        'movement_id',
        'processed_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the receipt.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the movement associated with the receipt.
     */
    public function movement()
    {
        return $this->belongsTo(Movement::class);
    }
}