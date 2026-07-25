<?php

namespace App\Actions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\ApprovalThreshold;
use App\Models\Core\Employee;
use App\Models\Core\Transaction;
use App\Models\Core\Account;
use Illuminate\Support\Facades\DB;

class WithdrawAction
{
    public function handle(string $accountCode, float $amount, Employee $employee): Transaction
    {
        return DB::transaction(function () use ($accountCode, $amount, $employee) {
            $account = Account::where('code', $accountCode)->lockForUpdate()->firstOrFail();

            if (! $account->is_active) {
                throw new TransactionRejectedException('Ce compte a ete desactive.');
            }

            if ($amount <= 0) {
                throw new TransactionRejectedException('Le montant doit etre superieur a 0.');
            }

            if ($account->typeOfAccount->active_case_payments === true) {
                throw new TransactionRejectedException('Vous ne pouvez pas faire de retrait sur ce type compte.');
            }

            // Inclut desormais les retraits ET les transferts sortants en
            // attente - voir Account::availableBalance().
            if ($amount > $account->availableBalance()) {
                throw new TransactionRejectedException('Solde disponible insuffisant.');
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::Withdrawal, $amount);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $amount,
                'employee_id' => $employee->id,
                'type' => TransactionType::Withdrawal,
                'status' => $status,
            ]);

            if ($status === TransactionStatus::Completed) {
                $account->decrement('balance', $amount);
            }

            return $transaction;
        });
    }
}