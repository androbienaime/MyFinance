<?php

namespace App\Actions;

use App\Enums\QrPaymentStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\Currency;
use App\Models\Core\Customer;
use App\Models\Core\QrPaymentRequest;
use App\Models\Core\Transaction;
use App\Notifications\QrPaymentConfirmed;
use App\Notifications\QrPaymentReceived;
use Illuminate\Support\Facades\DB;

class ProcessQrPaymentAction
{
    private const MAX_PIN_ATTEMPTS = 3;

    public function handle(string $reference, Customer $customer, string $payingAccountCode, ?string $pin = null): QrPaymentRequest
    {
        
        return DB::transaction(function () use ($reference, $customer, $payingAccountCode, $pin) {
            $paymentRequest = QrPaymentRequest::where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if (! $paymentRequest) {
                throw new TransactionRejectedException('Ce code QR est invalide ou introuvable.');
            }

            if ($paymentRequest->status !== QrPaymentStatus::Pending) {
                throw new TransactionRejectedException('Ce paiement n\'est plus disponible (deja paye, expire ou annule).');
            }

            if ($paymentRequest->isExpired()) {
                $paymentRequest->update(['status' => QrPaymentStatus::Expired]);
                throw new TransactionRejectedException('Ce code QR a expire.');
            }

            if ($customer->hasPinSet()) {
                if (! $pin) {
                    throw new TransactionRejectedException('Un code PIN est requis pour ce paiement.');
                }

                if ($paymentRequest->pin_attempts >= self::MAX_PIN_ATTEMPTS) {
                    $paymentRequest->update(['status' => QrPaymentStatus::Failed]);
                    throw new TransactionRejectedException('Trop de tentatives de PIN incorrectes. Paiement annule.');
                }

                if (! $customer->verifyPin($pin)) {
                    $paymentRequest->increment('pin_attempts');
                    throw new TransactionRejectedException('Code PIN incorrect.');
                }
            }

            $payingAccount = Account::where('code', $payingAccountCode)
                ->with(['typeOfAccount', 'currency'])
                ->lockForUpdate()
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

            $merchant = $paymentRequest->merchantProfile()->lockForUpdate()->first();
            $merchantAccount = $merchant->account()->with('currency')->lockForUpdate()->first();

            if (! $merchantAccount->is_active) {
                throw new TransactionRejectedException('Ce marchand n\'est plus actif.');
            }

            // Montants figes sur la demande, exprimes dans la devise du
            // marchand (voir GenerateQrPaymentRequestAction).
      
            $amountInMerchantCurrency = (float) $paymentRequest->amount;
            $feeInMerchantCurrency = (float) $paymentRequest->fee_amount;
            $totalInMerchantCurrency = (float) $paymentRequest->total_amount;

            $exchangeRateApplied = 1.0;
            $totalToDebit = $totalInMerchantCurrency;
            $amountToDebit = $amountInMerchantCurrency;
            $feeToDebit = $feeInMerchantCurrency;

            if ($payingAccount->currency_id !== $merchantAccount->currency_id) {
                $merchantCurrency = $merchantAccount->currency;
                $payingCurrency = $payingAccount->currency;

                $totalToDebit = $merchantCurrency->convertTo($totalInMerchantCurrency, $payingCurrency);
                $amountToDebit = $merchantCurrency->convertTo($amountInMerchantCurrency, $payingCurrency);
                $feeToDebit = $merchantCurrency->convertTo($feeInMerchantCurrency, $payingCurrency);

                // Trace le taux effectif reellement applique (ratio des
                // deux montants), plutot que de recalculer separement -
                // garantit que exchange_rate_applied correspond exactement
                // au calcul reel, meme avec l'arrondi a decimal_places.
                $exchangeRateApplied = $amountInMerchantCurrency > 0
                    ? round($amountToDebit / $amountInMerchantCurrency, 6)
                    : 1.0;
            }

            if ($totalToDebit > $payingAccount->availableBalance()) {
                throw new TransactionRejectedException('Solde disponible insuffisant.');
            }

            if ($totalToDebit > $payingAccount->availableBalance()) {
                throw new TransactionRejectedException('Solde disponible insuffisant.');
            }

            $feesAccount = null;

            if ($feeToDebit > 0) {
                $feesAccount = Account::where('code', setting('financial.fees_account_code'))
                    ->lockForUpdate()
                    ->first();

                if (! $feesAccount) {
                    throw new TransactionRejectedException('Compte de frais introuvable - contactez un administrateur.');
                }
            }

            $mainTransaction = $this->createLegsAndApplyBalances(
                $payingAccount,
                $merchantAccount,
                $feesAccount,
                amountToDebit: $amountToDebit,
                amountToCredit: $amountInMerchantCurrency, // le marchand recoit toujours dans SA devise, montant inchange
                feeToDebit: $feeToDebit,
                exchangeRateApplied: $exchangeRateApplied,
            );

            $paymentRequest->update([
                'status' => QrPaymentStatus::Completed,
                'customer_id' => $customer->id,
                'paying_account_id' => $payingAccount->id,
                'transaction_id' => $mainTransaction->id,
                'paid_at' => now(),
            ]);

            $customer->notify(new QrPaymentConfirmed($paymentRequest, $merchant));
            $merchant->notify(new QrPaymentReceived($paymentRequest, $customer));

            return $paymentRequest->refresh();
        });
    }

    /**
     * Taux pour convertir un montant exprime dans $from vers $to, en
     * passant par la devise par defaut du systeme comme pivot - meme
     * convention que exchange_rate ailleurs dans MyFinance (toujours
     * relatif a la devise par defaut).
     */
    private function exchangeRate(Currency $from, Currency $to): float
    {
        $default = Currency::default();

        $fromRateToDefault = $from->id === $default->id ? 1.0 : (float) $from->exchange_rate;
        $toRateToDefault = $to->id === $default->id ? 1.0 : (float) $to->exchange_rate;

        // Montant en devise par defaut, puis reconverti vers $to.
        return $fromRateToDefault / $toRateToDefault;
    }

    private function createLegsAndApplyBalances(
        Account $payingAccount,
        Account $merchantAccount,
        ?Account $feesAccount,
        float $amountToDebit,
        float $amountToCredit,
        float $feeToDebit,
        float $exchangeRateApplied,
    ): Transaction {
        $debitMain = Transaction::create([
            'account_id' => $payingAccount->id,
            'counterparty_account_id' => $merchantAccount->id,
            'direction' => TransactionDirection::Debit,
            'code' => Transaction::generateUniqueCode(),
            'amount' => $amountToDebit,
            'exchange_rate_applied' => $exchangeRateApplied,
            'employee_id' => null,
            'type' => TransactionType::QrPayment,
            'status' => TransactionStatus::Completed,
        ]);

        Transaction::create([
            'account_id' => $merchantAccount->id,
            'counterparty_account_id' => $payingAccount->id,
            'direction' => TransactionDirection::Credit,
            'code' => Transaction::generateUniqueCode(),
            'amount' => $amountToCredit,
            'exchange_rate_applied' => $exchangeRateApplied,
            'employee_id' => null,
            'type' => TransactionType::QrPayment,
            'status' => TransactionStatus::Completed,
        ]);

        $payingAccount->decrement('balance', $amountToDebit);
        $merchantAccount->increment('balance', $amountToCredit);

        if ($feeToDebit > 0 && $feesAccount) {
            Transaction::create([
                'account_id' => $payingAccount->id,
                'counterparty_account_id' => $feesAccount->id,
                'direction' => TransactionDirection::Debit,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $feeToDebit,
                'employee_id' => null,
                'type' => TransactionType::QrPaymentFee,
                'status' => TransactionStatus::Completed,
            ]);

            Transaction::create([
                'account_id' => $feesAccount->id,
                'counterparty_account_id' => $payingAccount->id,
                'direction' => TransactionDirection::Credit,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $feeToDebit,
                'employee_id' => null,
                'type' => TransactionType::QrPaymentFee,
                'status' => TransactionStatus::Completed,
            ]);

            $payingAccount->decrement('balance', $feeToDebit);
            $feesAccount->increment('balance', $feeToDebit);
        }

        return $debitMain;
    }
}