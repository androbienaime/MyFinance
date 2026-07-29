<?php

namespace App\Actions;

use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\ApprovalThreshold;
use App\Models\Core\Currency;
use App\Models\Core\Customer;
use App\Models\Core\Employee;
use App\Models\Core\P2pTransferFeeTier;
use App\Models\Core\Transaction;
use App\Services\TransferNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferAction
{
    public function __construct(private TransferNotifier $notifier) {}

    public function handle(
        string $fromAccountCode,
        string $toAccountCode,
        float $amount,
        ?Employee $employee = null,
        ?Customer $initiatingCustomer = null,
        float $feeAmount = 0.0,
    ): array {
        if ($fromAccountCode === $toAccountCode) {
            throw new TransactionRejectedException('Le compte source et destinataire ne peuvent pas etre identiques.');
        }

        if ($amount <= 0) {
            throw new TransactionRejectedException('Le montant doit etre superieur a 0.');
        }

        return DB::transaction(function () use ($fromAccountCode, $toAccountCode, $amount, $employee, $initiatingCustomer, $feeAmount) {
            $accounts = Account::whereIn('code', [$fromAccountCode, $toAccountCode])
                ->with('typeOfAccount', 'customer', 'currency')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($account) => strtolower($account->code));

            $fromKey = strtolower($fromAccountCode);
            $toKey   = strtolower($toAccountCode);

            if (!$accounts->has($fromKey) || !$accounts->has($toKey)) {
                throw new TransactionRejectedException('Compte source ou destinataire introuvable.');
            }

            $from = $accounts->get($fromKey);
            $to   = $accounts->get($toKey);

            if (! $from->is_active) {
                throw new TransactionRejectedException('Le compte source est desactive.');
            }

            if (! $to->is_active) {
                throw new TransactionRejectedException('Le compte destinataire est desactive.');
            }

            if ((bool) $from->typeOfAccount->active_case_payments) {
                throw new TransactionRejectedException('Impossible de transferer depuis un compte a paiement par cases.');
            }

            if ((bool) $to->typeOfAccount->active_case_payments) {
                throw new TransactionRejectedException('Impossible de transferer vers un compte a paiement par cases.');
            }

            $sourceCurrency = $from->currency;
            $destinationCurrency = $to->currency;

            if (! $sourceCurrency->is_active || ! $destinationCurrency->is_active) {
                throw new TransactionRejectedException('Devise source ou destinataire desactivee.');
            }

            $sameCurrency = $sourceCurrency->is($destinationCurrency);

            // Taux fige au moment T - ne jamais recalculer a posteriori
            // avec le taux courant, sous peine de fausser l'historique
            // comptable si le taux change entre-temps.
            $exchangeRateApplied = $sameCurrency ? null : $sourceCurrency->rateTo($destinationCurrency);

            // Montant credite au destinataire, dans SA devise. C'est le
            // point critique : ne jamais crediter $amount brut si les
            // devises different, sous peine de crediter le mauvais montant.
            $destinationAmount = $sameCurrency
                ? $amount
                : $sourceCurrency->convertTo($amount, $destinationCurrency);

            // Le bareme de frais (P2pTransferFeeTier) est exprime dans la
            // devise pivot (par defaut). On convertit le montant transfere
            // vers cette devise pour trouver le bon palier, puis on
            // reconvertit le frais resultant dans la devise source (car
            // c'est le compte source qui est debite des frais).
            $defaultCurrency = Currency::where("iso_code", setting("financial.default_currency"))->first();
            $amountInDefaultCurrency = $sourceCurrency->is($defaultCurrency)
                ? $amount
                : $sourceCurrency->convertTo($amount, $defaultCurrency);

            $feeAmount = setting('financial.fee_for_transfer_in_branch_enabled', default: false)
                ? P2pTransferFeeTier::feeFor($amountInDefaultCurrency)
                : 0;

            if ($feeAmount > 0 && ! $sourceCurrency->is($defaultCurrency)) {
                $feeAmount = $defaultCurrency->convertTo($feeAmount, $sourceCurrency);
            }

            $totalDebit = $amount + $feeAmount;

            if ($totalDebit > $from->availableBalance()) {
                throw new TransactionRejectedException(
                    'Solde disponible insuffisant' . ($feeAmount > 0 ? ' (frais inclus).' : '.')
                );
            }

            $feesAccount = null;
            $feeAmountForFeesAccount = 0.0;
            $feeExchangeRateApplied = null;

            if ($feeAmount > 0) {
                $feesAccount = Account::where('code', setting('financial.fees_account_code'))
                    ->with('currency')
                    ->lockForUpdate()
                    ->first();

                if (! $feesAccount) {
                    throw new TransactionRejectedException('Compte de frais introuvable - contactez un administrateur.');
                }

                $feesCurrency = $feesAccount->currency;
                $feeSameCurrency = $sourceCurrency->is($feesCurrency);

                $feeAmountForFeesAccount = $feeSameCurrency
                    ? $feeAmount
                    : $sourceCurrency->convertTo($feeAmount, $feesCurrency);

                $feeExchangeRateApplied = $feeSameCurrency ? null : $sourceCurrency->rateTo($feesCurrency);
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::Transfer, $amount);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $groupId = (string) Str::uuid();

            $common = [
                'transfer_group_id' => $groupId,
                'employee_id' => $employee?->id,
                'initiated_by_customer_id' => $initiatingCustomer?->id,
                'status' => $status,
            ];

            $debitPrincipal = Transaction::create($common + [
                'account_id' => $from->id,
                'counterparty_account_id' => $to->id,
                'direction' => TransactionDirection::Debit,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $amount,
                'currency_id' => $sourceCurrency->id,
                'exchange_rate_applied' => null, // la jambe debit est toujours dans sa propre devise native
                'type' => TransactionType::Transfer,
            ]);

            $creditPrincipal = Transaction::create($common + [
                'account_id' => $to->id,
                'counterparty_account_id' => $from->id,
                'direction' => TransactionDirection::Credit,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $destinationAmount,
                'currency_id' => $destinationCurrency->id,
                'exchange_rate_applied' => $exchangeRateApplied,
                'type' => TransactionType::Transfer,
            ]);

            $legs = [$debitPrincipal, $creditPrincipal];

            if ($feeAmount > 0 && $feesAccount) {
                $legs[] = Transaction::create($common + [
                    'account_id' => $from->id,
                    'counterparty_account_id' => $feesAccount->id,
                    'direction' => TransactionDirection::Debit,
                    'code' => Transaction::generateUniqueCode(),
                    'amount' => $feeAmount,
                    'currency_id' => $sourceCurrency->id,
                    'exchange_rate_applied' => null,
                    'type' => TransactionType::TransferFee,
                ]);

                $legs[] = Transaction::create($common + [
                    'account_id' => $feesAccount->id,
                    'counterparty_account_id' => $from->id,
                    'direction' => TransactionDirection::Credit,
                    'code' => Transaction::generateUniqueCode(),
                    'amount' => $feeAmountForFeesAccount,
                    'currency_id' => $feesAccount->currency->id,
                    'exchange_rate_applied' => $feeExchangeRateApplied,
                    'type' => TransactionType::TransferFee,
                ]);
            }

            if ($status === TransactionStatus::Completed) {
                $from->decrement('balance', $totalDebit);
                $to->increment('balance', $destinationAmount); // <- correction: destinationAmount, pas $amount
                $feesAccount?->increment('balance', $feeAmountForFeesAccount);

                $this->notifier->notifyTransferCompleted($from, $to, $amount, $feeAmount);
            }

            return $legs;
        });
    }
}