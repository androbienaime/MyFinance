<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class P2pTransferRequest extends Model
{
    protected $fillable = [
        'customer_id', 'from_account_id', 'to_account_id',
        'amount', 'fee_amount', 'otp_code_hash', 'attempts',
        'expires_at', 'status', 'confirmed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}