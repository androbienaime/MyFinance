<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    public function accounts() : BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_person')
            ->withPivot([
                'role',
                'permissions',
                'share_percentage',
                'start_date',
                'end_date',
                'is_active'
            ])
            ->withTimestamps();
    }
}
