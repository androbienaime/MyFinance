<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

        public function earlyWithdrawalFee(): HasOne
    {
        return $this->hasOne(EarlyWithdrawalFee::class);
    }

    /**
     * Pourcentage de frais applicable : la config specifique a ce type
     * si elle existe et est active, sinon le pourcentage global par
     * defaut. Retourne 0 si aucune des deux n'est configuree.
     */
    public function earlyWithdrawalFeePercentage(): float
    {
        $specific = $this->earlyWithdrawalFee;

        if ($specific && $specific->is_active) {
            return (float) $specific->fee_percentage;
        }

        return (float) setting('financial.default_early_withdrawal_fee_percentage', default:0);
    }
}