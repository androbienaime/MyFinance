<?php

// app/Models/Core/P2pTransferLimit.php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class P2pTransferLimit extends Model
{
    protected $fillable = [
        'max_per_transaction', 'max_daily_amount',
        'max_daily_count', 'max_monthly_amount', 'is_active',
    ];

    protected $casts = [
        'max_per_transaction' => 'decimal:2',
        'max_daily_amount' => 'decimal:2',
        'max_daily_count' => 'integer',
        'max_monthly_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function current(): self
    {
        return static::where('is_active', true)->latest()->firstOrFail();
    }
}