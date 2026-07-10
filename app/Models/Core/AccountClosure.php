<?php
// app/Models/AccountClosure.php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountClosure extends Model
{
    protected $fillable = [
        'account_id',
        'type',
        'reason',
        'balance_at_closure',
        'closed_by',
    ];

    protected $casts = [
        'balance_at_closure' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'closed_by');
    }
}