<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $fillable = [
        'email', 'user_id', 'branch_id', 'ip_address',
        'user_agent', 'status', 'failure_reason', 'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];
}