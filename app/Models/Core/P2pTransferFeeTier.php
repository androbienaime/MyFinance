<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class P2pTransferFeeTier extends Model
{
    protected $fillable = ['min_amount', 'max_amount', 'fee_amount', 'is_active'];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function feeFor(float $amount): float
    {
        $tier = static::query()
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('min_amount')
            ->first();

        return (float) ($tier->fee_amount ?? 0);
    }
}