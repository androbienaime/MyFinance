<?php

// app/Models/Core/MerchantProfile.php
namespace App\Models\Core;

use App\Enums\MerchantStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MerchantProfile extends Authenticatable
{
    // use HasApiTokens; 
    use Notifiable;

    protected $fillable = [
        'account_id', 'business_name', 'category', 'business_registration_number',
        'address', 'transaction_fee_percentage', 'status',
        'approved_by', 'approved_at', 'rejection_reason',
        'api_password', 'api_password_changed_at',
    ];

    protected $hidden = ['api_password'];

    protected function casts(): array
    {
        return [
            'status' => MerchantStatus::class,
            'transaction_fee_percentage' => 'decimal:2',
            'approved_at' => 'datetime',
            'api_password_changed_at' => 'datetime',
            'api_password' => 'hashed',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isActive(): bool
    {
        return $this->status === MerchantStatus::Active;
    }

    public function transactionFeePercentage(): float
    {
        return (float) ($this->transaction_fee_percentage
            ?? setting('merchants.default_transaction_fee_percentage', 0));
    }
}