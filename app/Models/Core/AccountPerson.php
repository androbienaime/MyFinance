<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class AccountPerson extends Model
{
    protected $table = 'account_person';

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
