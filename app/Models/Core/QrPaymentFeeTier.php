<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrPaymentFeeTier extends Model
{
    protected $fillable = ['merchant_profile_id', 'currency_id', 'min_amount', 'max_amount', 'fee_percentage', 'is_active'];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (QrPaymentFeeTier $tier) {
            $tier->currency_id ??= Currency::default()->id;
        });
    }

    public function merchantProfile(): BelongsTo
    {
        return $this->belongsTo(MerchantProfile::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @param float $amount Montant DEJA converti dans la devise par
     * defaut du systeme (voir QrPaymentFeeResolver) - cette methode ne
     * fait plus aucune conversion elle-meme.
     */
    public static function percentageFor(int $merchantProfileId, float $amount): float
    {
        // Seuls les paliers ACTIFS comptent pour determiner si le marchand
        // "a ses propres paliers" - un marchand avec uniquement des paliers
        // desactives doit retomber sur le repli global, pas rester bloque
        // sans aucun palier exploitable.
        $hasOwnActiveTiers = static::where('merchant_profile_id', $merchantProfileId)
            ->where('is_active', true)
            ->exists();

        $tier = static::query()
            ->where('is_active', true)
            ->when(
                $hasOwnActiveTiers,
                fn ($q) => $q->where('merchant_profile_id', $merchantProfileId),
                fn ($q) => $q->whereNull('merchant_profile_id'),
            )
            ->where('min_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('min_amount')
            ->first();

        // 0 si aucun palier (specifique ou global) ne couvre ce montant -
        // le paiement passe sans frais, jamais d'echec pour cette raison.
        return (float) ($tier->fee_percentage ?? 0);
    }
}
