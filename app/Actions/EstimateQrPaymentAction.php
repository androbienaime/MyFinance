<?php

namespace App\Actions;

use App\Enums\QrPaymentStatus;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\Customer;
use App\Models\Core\QrPaymentRequest;

/**
 * Simule un paiement QR SANS rien débiter : calcule le montant qui serait prélevé
 * sur le compte choisi (conversion éventuelle), exactement comme ProcessQrPaymentAction.
 */
class EstimateQrPaymentAction
{
    public function handle(string $reference, Customer $customer, string $payingAccountCode): array
    {
        $paymentRequest = QrPaymentRequest::where('reference', $reference)->first();

        if (! $paymentRequest) {
            throw new TransactionRejectedException('Ce code QR est invalide ou introuvable.');
        }

        if ($paymentRequest->status !== QrPaymentStatus::Pending || $paymentRequest->isExpired()) {
            throw new TransactionRejectedException('Ce paiement n\'est plus disponible (deja paye, expire ou annule).');
        }

        $payingAccount = Account::where('code', $payingAccountCode)
            ->with(['typeOfAccount', 'currency'])
            ->first();

        if (! $payingAccount || ! $payingAccount->customer || $payingAccount->customer->id !== $customer->id) {
            throw new TransactionRejectedException('Ce compte ne vous appartient pas ou est introuvable.');
        }

        if (! $payingAccount->is_active) {
            throw new TransactionRejectedException('Ce compte est desactive.');
        }

        if ((bool) $payingAccount->typeOfAccount->active_case_payments) {
            throw new TransactionRejectedException('Ce type de compte ne peut pas etre utilise pour un paiement QR.');
        }

        $merchantAccount = $paymentRequest->merchantProfile->account()->with('currency')->first();

        // Montants figés sur la demande, dans la devise du marchand.
        $amount = (float) $paymentRequest->amount;
        $fee = (float) $paymentRequest->fee_amount;
        $total = (float) $paymentRequest->total_amount;

        $converted = $payingAccount->currency_id !== $merchantAccount->currency_id;
        $rate = 1.0;

        if ($converted) {
            $from = $merchantAccount->currency;
            $to = $payingAccount->currency;

            $amountToDebit = $from->convertTo($amount, $to);
            $feeToDebit = $from->convertTo($fee, $to);
            $totalToDebit = $from->convertTo($total, $to);
            $rate = $amount > 0 ? round($amountToDebit / $amount, 6) : 1.0;
        } else {
            $amountToDebit = $amount;
            $feeToDebit = $fee;
            $totalToDebit = $total;
        }

        $available = (float) $payingAccount->availableBalance();
        $currency = $payingAccount->currency;

        return [
            'account_code' => $payingAccount->code,
            'converted' => $converted,
            'currency' => ['code' => $currency->iso_code ?? $currency->code],
            'amount' => $amountToDebit,
            'fee_amount' => $feeToDebit,
            'total_amount' => $totalToDebit,
            'exchange_rate' => $rate,
            'available_balance' => $available,
            'sufficient' => $totalToDebit <= $available,
        ];
    }
}
