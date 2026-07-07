<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class AccountPerson extends Model
{
    protected $table = 'account_people';

    protected $fillable = [
        'account_id',
        'person_id',
        'role',
        'permissions',
        'share_percentage',
        'start_date',
        'end_date',
        'is_active'
    ];

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
