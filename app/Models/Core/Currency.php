<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'iso_code', 'name', 'symbol', 'decimal_places',
        'exchange_rate', 'is_default', 'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public static function default(): self
    {
        return static::where('iso_code', setting("financial.default_currency", default:'HTG'))->firstOrFail();
    }

    public function convertTo(float $amount, Currency $target): float
    {
        // passage par la devise pivot (ex: HTG à taux 1)
        $inPivot = $amount / $this->exchange_rate;
        return round($inPivot * $target->exchange_rate, $target->decimal_places);
    }

       /**
     * Taux de conversion source -> target, via la devise pivot.
     */
    public function rateTo(Currency $target): float
    {
        return $target->exchange_rate == 0
            ? throw new \RuntimeException("Taux de change invalide pour {$target->code}")
            : $this->exchange_rate / $target->exchange_rate;
    }

    public function format(float $amount): string
    {
        return $this->symbol . ' ' . number_format($amount, $this->decimal_places);
    }
}