<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class CustomerLoginAttempt extends Model
{
    protected $fillable = [
        'identifier', 'customer_id', 'ip_address',
        'user_agent', 'status', 'attempted_at',
    ];

    protected $casts = ['attempted_at' => 'datetime'];
}