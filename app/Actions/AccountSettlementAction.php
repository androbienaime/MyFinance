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
    public function handle(string $accountCode, float $amount, Employee $employee): Transaction
    {
        return DB::transaction(function () use ($accountCode, $employee) {
            $account = Account::where('code', $accountCode)->with('typeOfAccount')->lockForUpdate()->firstOrFail();

            if (! $account->is_active) {
                throw new TransactionRejectedException('Ce compte a ete desactive.');
            }

            $balanceBeforeSettlement = (float) ($account->balance ?? 0);
            $feeAmount = $account->earlyWithdrawalFeeAmount();
            $netAmount = $balanceBeforeSettlement - $feeAmount;

            $feesAccount = null;

            if ($feeAmount > 0) {
                $feesAccount = Account::where('code', setting('financial.fees_account_code'))
                    ->lockForUpdate()
                    ->first();

                if (! $feesAccount) {
                    throw new TransactionRejectedException('Compte de frais introuvable - contactez un administrateur.');
                }
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::AccountSettlement, $balanceBeforeSettlement);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $groupId = $feeAmount > 0 ? (string) Str::uuid() : null;

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transfer_group_id' => $groupId,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $netAmount,
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
                    'employee_id' => $employee->id,
                    'type' => TransactionType::SettlementFee,
                    'status' => $status,
                ]);
            }

            if ($status === TransactionStatus::Completed) {
                $closure = $account->closures()->create([
                    'type' => TransactionType::AccountSettlement,
                    'reason' => $feeAmount > 0
                        ? "Retrait anticipe - frais de {$feeAmount} preleve(s)"
                        : 'Account Settlement',
                    'balance_at_closure' => $balanceBeforeSettlement,
                    'closed_by' => $employee->id,
                ]);

                $feesAccount?->increment('balance', $feeAmount);

                $account->balance = 0;
                $account->is_active = false;
                $account->save();
            }

            return $transaction;
        });
    }
}