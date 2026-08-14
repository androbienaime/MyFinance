<?php

// app/Models/Core/QrPaymentRequest.php
namespace App\Models\Core;

use App\Enums\QrPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrPaymentRequest extends Model
{
    protected $fillable = [
        'reference', 'merchant_profile_id', 'merchant_api_key_id', 'description',
        'amount', 'fee_amount', 'total_amount',
        'customer_id', 'paying_account_id', 'transaction_id',
        'status', 'pin_attempts', 'expires_at', 'paid_at',
        'currency_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => QrPaymentStatus::class,
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payingAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'paying_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }

    public function isPayable(): bool
    {
        return $this->status === QrPaymentStatus::Pending && ! $this->isExpired();
    }
}