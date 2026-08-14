<?php

namespace App\Services;

use App\Models\Core\Currency;
use App\Models\Core\MerchantProfile;
use App\Models\Core\QrPaymentFeeTier;

class QrPaymentFeeResolver
{
    /**
     * Determine le pourcentage de frais applicable. Jamais bloquant :
     * si aucun palier specifique ni global ne correspond au montant,
     * QrPaymentFeeTier::percentageFor() retourne 0 - le paiement passe
     * simplement sans frais plutot que d'echouer.
     */
    public function resolve(MerchantProfile $merchant, float $amount, Currency $paymentCurrency): float
    {
        // Override manuel sur le marchand : prioritaire, aucune conversion
        // necessaire puisque c'est un pourcentage fixe, pas un montant.
        // if (! is_null($merchant->transaction_fee_percentage)) {
        //     return (float) $merchant->transaction_fee_percentage;
        // }

        $defaultCurrency = Currency::where('iso_code', setting('financial.default_currency', default:'HTG'))->first();

        $amountInDefaultCurrency = $paymentCurrency->id === $defaultCurrency->id
            ? $amount
            : $paymentCurrency->convertTo($amount, $defaultCurrency);

        // Retourne 0 si aucun palier (specifique ou global) ne
        // correspond - jamais d'exception, jamais de blocage.
        return QrPaymentFeeTier::percentageFor($merchant->id, $amountInDefaultCurrency);
    }
}