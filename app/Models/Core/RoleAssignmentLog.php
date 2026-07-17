<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class RoleAssignmentLog extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'assigned_by',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];
}
