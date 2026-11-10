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

    /** Montants réellement débités (devise du compte payeur), lus par le contrôleur pour la réponse de /pay. */
    public ?array $debited = null;

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

            if ($customer->hasPin()) {
                if (! $pin) {
                    throw new TransactionRejectedException('Un code PIN est requis pour ce paiement.');
                }

                if ($paymentRequest->pin_attempts >= self::MAX_PIN_ATTEMPTS) {
                    $paymentRequest->update(['status' => QrPaymentStatus::Failed]);
                    throw new TransactionRejectedException('Trop de tentatives de PIN incorrectes. Paiement annule.');
                }

                if (! $customer->checkPin($pin)) {
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
            $amountToDebit = $amountInMerchantCurrency;
            $feeToDebit = $feeInMerchantCurrency;

            if ($payingAccount->currency_id !== $merchantAccount->currency_id) {
                $merchantCurrency = $merchantAccount->currency;
                $payingCurrency = $payingAccount->currency;

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

            // Le total debite est la somme des deux montants converts (et non
            // une conversion separee du total) : c'est exactement ce qui sera
            // decremente du solde, donc la verification de solde reste coherente
            // meme avec l'arrondi a decimal_places.
            $totalToDebit = $amountToDebit + $feeToDebit;

            if ($totalToDebit > $payingAccount->availableBalance()) {
                throw new TransactionRejectedException('Solde disponible insuffisant.');
            }

            $debitedCurrency = $payingAccount->currency;
            $this->debited = [
                'amount' => $amountToDebit,
                'fee_amount' => $feeToDebit,
                'total_amount' => $totalToDebit,
                'currency' => ['code' => $debitedCurrency->iso_code ?? $debitedCurrency->code],
                'exchange_rate' => $exchangeRateApplied,
            ];

            $feesAccount = null;
            $feeToCredit = $feeToDebit;
            $feeExchangeRateApplied = 1.0;

            if ($feeToDebit > 0) {
                $feesAccount = Account::where('code', setting('financial.fees_account_code'))
                    ->with('currency')
                    ->lockForUpdate()
                    ->first();

                if (! $feesAccount) {
                    throw new TransactionRejectedException('Compte de frais introuvable - contactez un administrateur.');
                }

                // Le compte de frais peut etre dans une devise differente de
                // celle du payeur : on convertit le montant a crediter.
                if ($feesAccount->currency_id === $payingAccount->currency_id) {
                    $feeToCredit = $feeToDebit;
                } elseif ($feesAccount->currency_id === $merchantAccount->currency_id) {
                    $feeToCredit = $feeInMerchantCurrency;
                } else {
                    // Conversion depuis la devise d'origine (marchand) pour
                    // eviter un double arrondi.
                    $feeToCredit = $merchantAccount->currency->convertTo($feeInMerchantCurrency, $feesAccount->currency);
                }

                // Taux effectif reellement applique entre le debit (devise
                // payeur) et le credit (devise du compte de frais).
                $feeExchangeRateApplied = $feeToCredit !== $feeToDebit
                    ? round($feeToCredit / $feeToDebit, 6)
                    : 1.0;
            }

            $mainTransaction = $this->createLegsAndApplyBalances(
                $payingAccount,
                $merchantAccount,
                $feesAccount,
                amountToDebit: $amountToDebit,
                amountToCredit: $amountInMerchantCurrency, // le marchand recoit toujours dans SA devise, montant inchange
                feeToDebit: $feeToDebit,
                feeToCredit: $feeToCredit,
                exchangeRateApplied: $exchangeRateApplied,
                feeExchangeRateApplied: $feeExchangeRateApplied,
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

    private function createLegsAndApplyBalances(
        Account $payingAccount,
        Account $merchantAccount,
        ?Account $feesAccount,
        float $amountToDebit,
        float $amountToCredit,
        float $feeToDebit,
        float $feeToCredit,
        float $exchangeRateApplied,
        float $feeExchangeRateApplied = 1.0,
    ): Transaction {
        $debitMain = Transaction::create([
            'account_id' => $payingAccount->id,
            'counterparty_account_id' => $merchantAccount->id,
            'currency_id' => $payingAccount->currency_id,
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
            'currency_id' => $merchantAccount->currency_id,
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
                'currency_id' => $payingAccount->currency_id,
                'direction' => TransactionDirection::Debit,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $feeToDebit,
                'exchange_rate_applied' => $feeExchangeRateApplied,
                'employee_id' => null,
                'type' => TransactionType::QrPaymentFee,
                'status' => TransactionStatus::Completed,
            ]);

            Transaction::create([
                'account_id' => $feesAccount->id,
                'counterparty_account_id' => $payingAccount->id,
                'currency_id' => $feesAccount->currency_id,
                'direction' => TransactionDirection::Credit,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $feeToCredit,
                'exchange_rate_applied' => $feeExchangeRateApplied,
                'employee_id' => null,
                'type' => TransactionType::QrPaymentFee,
                'status' => TransactionStatus::Completed,
            ]);

            $payingAccount->decrement('balance', $feeToDebit);
            $feesAccount->increment('balance', $feeToCredit);
        }

        return $debitMain;
    }
}