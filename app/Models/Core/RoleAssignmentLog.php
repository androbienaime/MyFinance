<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function user() : BelongsTo{
        return $this->belongsTo(User::class);
    }

    // public function roles(): BelongsToMany
    // {
    //     return $this->belongsToMany(
    //         Role::class,
    //         'role_assignment_role', // ta table pivot custom
    //         'role_assignment_id',
    //         'role_id'
    //     );
    // }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
