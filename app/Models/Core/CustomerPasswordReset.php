<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class CustomerPasswordReset extends Model
{
    protected $fillable = ['customer_id', 'otp_code_hash', 'attempts', 'expires_at', 'status'];

    protected $casts = ['expires_at' => 'datetime'];
}
