<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaisseSessionBalance extends Model
{
    protected $fillable = [
        'caisse_session_id',
        'currency_id',
        'opening_balance_declared',
        'opening_balance_expected',
        'opening_discrepancy',
        'total_deposits',
        'total_withdrawals',
        'total_other_movements',
        'closing_balance_expected',
        'closing_balance_declared',
        'closing_discrepancy',
        'closing_comment',
    ];

    protected $casts = [
        'opening_balance_declared' => 'decimal:2',
        'opening_balance_expected' => 'decimal:2',
        'opening_discrepancy' => 'decimal:2',
        'total_deposits' => 'decimal:2',
        'total_withdrawals' => 'decimal:2',
        'total_other_movements' => 'decimal:2',
        'closing_balance_expected' => 'decimal:2',
        'closing_balance_declared' => 'decimal:2',
        'closing_discrepancy' => 'decimal:2',
    ];

    public function caisseSession(): BelongsTo
    {
        return $this->belongsTo(CaisseSession::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function hasDiscrepancy(): bool
    {
        return $this->closing_discrepancy !== null && abs((float) $this->closing_discrepancy) > 0.0;
    }
}