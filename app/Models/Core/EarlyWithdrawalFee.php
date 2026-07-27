<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarlyWithdrawalFee extends Model
{
    protected $fillable = ['type_of_account_id', 'fee_percentage', 'is_active'];

    protected $casts = [
        'fee_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function typeOfAccount(): BelongsTo
    {
        return $this->belongsTo(TypeOfAccount::class);
    }
}