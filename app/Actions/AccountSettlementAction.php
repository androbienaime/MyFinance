<?php

namespace App\Actions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\ApprovalThreshold;
use App\Models\Core\Employee;
use App\Models\Core\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSettlementAction
{
    /**
     * Cloture un compte et solde le montant restant, moins d'eventuels
     * frais de retrait anticipe. Le montant est TOUJOURS recalcule a
     * partir du solde reel en DB (jamais depuis un montant fourni par
     * l'appelant) - un compte se solde integralement, pas partiellement.
     */
    public function handle(string $accountCode, Employee $employee): Transaction
    {
        return DB::transaction(function () use ($accountCode, $employee) {
            $account = Account::where('code', $accountCode)
                ->with('typeOfAccount', 'currency')
                ->lockForUpdate()
                ->firstOrFail();

            if (! $account->is_active) {
                throw new TransactionRejectedException('Ce compte a ete desactive.');
            }

            $accountCurrency = $account->currency;

            $balanceBeforeSettlement = (float) ($account->balance ?? 0);
            $feeAmount = $account->earlyWithdrawalFeeAmount(); // deja dans la devise du compte
            $netAmount = $balanceBeforeSettlement - $feeAmount;

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
                $sameCurrency = $accountCurrency->is($feesCurrency);

                $feeAmountForFeesAccount = $sameCurrency
                    ? $feeAmount
                    : $accountCurrency->convertTo($feeAmount, $feesCurrency);

                $feeExchangeRateApplied = $sameCurrency ? null : $accountCurrency->rateTo($feesCurrency);
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::AccountSettlement, $balanceBeforeSettlement);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $groupId = $feeAmount > 0 ? (string) Str::uuid() : null;

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transfer_group_id' => $groupId,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $netAmount,
                'currency_id' => $accountCurrency->id,
                'exchange_rate_applied' => null, // jambe compte, toujours dans sa devise native
                'employee_id' => $employee->id,
                'type' => TransactionType::AccountSettlement,
                'status' => $status,
            ]);

            $feeTransaction = null;

            if ($feeAmount > 0) {
                $feeTransaction = Transaction::create([
                    'account_id' => $account->id,
                    'counterparty_account_id' => $feesAccount->id,
                    'transfer_group_id' => $groupId,
                    'code' => Transaction::generateUniqueCode(),
                    'amount' => $feeAmount,
                    'currency_id' => $accountCurrency->id,
                    'exchange_rate_applied' => null,
                    'employee_id' => $employee->id,
                    'type' => TransactionType::SettlementFee,
                    'status' => $status,
                ]);
            }

            if ($status === TransactionStatus::Completed) {
                $closure = $account->closures()->create([
                    'type' => TransactionType::AccountSettlement,
                    'reason' => $feeAmount > 0
                        ? "Retrait anticipe - frais de {$accountCurrency->format($feeAmount)} preleve(s)"
                        : 'Account Settlement',
                    'balance_at_closure' => $balanceBeforeSettlement,
                    'closed_by' => $employee->id,
                ]);

                $feesAccount?->increment('balance', $feeAmountForFeesAccount); // montant CONVERTI

                $account->balance = 0;
                $account->is_active = false;
                $account->save();
            }

            return $transaction;
        });
    }
}