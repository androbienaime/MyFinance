<?php

// app/Models/Core/MerchantIdempotencyKey.php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class MerchantIdempotencyKey extends Model
{
    protected $fillable = [
        'merchant_api_key_id', 'idempotency_key', 'request_hash',
        'response_status', 'response_body', 'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'expires_at' => 'datetime',
    ];
}