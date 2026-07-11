<?php

namespace App\Actions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\AccountClosure;
use App\Models\Core\ApprovalThreshold;
use App\Models\Core\Employee;
use App\Models\Core\Transaction;
use Illuminate\Support\Facades\DB;

class AccountRestorationAction
{
    public function handle(string $accountCode, Employee $employee): Transaction
    {
        return DB::transaction(function () use ($accountCode, $employee) {
            $account = Account::where('code', $accountCode)->lockForUpdate()->firstOrFail();

            if ($account->is_active) {
                throw new TransactionRejectedException('Ce compte est deja actif.');
            }

            // $lastSettlement = Transaction::where('account_id', $account->id)
            //     ->where('type', TransactionType::AccountClosure)
            //     ->where('status', TransactionStatus::Completed)
            //     ->latest('id')
            //     ->first();
            $lastSettlement = AccountClosure::where('account_id', $account->id)
                ->where('type', TransactionType::AccountClosure)
                // ->where('status', TransactionStatus::Completed)
                ->latest('id')
                ->first();

            if (! $lastSettlement) {
                throw new TransactionRejectedException('Aucune cloture trouvee pour ce compte.');
            }

            $restoredAmount = $lastSettlement->balance_at_closure;

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::AccountRestoration, $restoredAmount);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $restoredAmount,
                'employee_id' => $employee->id,
                'type' => TransactionType::AccountRestoration,
                'status' => $status,
            ]);

            if ($status === TransactionStatus::Completed) {
                $account->balance = $restoredAmount;
                $account->is_active = true;
                $account->save();
            }

            return $transaction;
        });
    }
}