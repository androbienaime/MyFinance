<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class MerchantLoginAttempt extends Model
{
    protected $fillable = [
        'identifier', 'merchant_profile_id', 'ip_address',
        'user_agent', 'status', 'attempted_at',
    ];

    protected $casts = ['attempted_at' => 'datetime'];
}