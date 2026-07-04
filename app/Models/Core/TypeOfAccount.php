<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration',
        'active_case_payments',
        'prefix',
    ];

    protected $casts = [
        'active_case_payments' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}