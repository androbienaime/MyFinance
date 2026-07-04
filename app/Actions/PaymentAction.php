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

class PaymentAction
{
    public function handle(string $accountCode, float $amount, Employee $employee): Transaction
    {
        return DB::transaction(function () use ($accountCode, $amount, $employee) {
            $account = Account::where('code', $accountCode)->lockForUpdate()->firstOrFail();

            if (! $account->is_active) {
                throw new TransactionRejectedException('Ce compte a ete desactive.');
            }

            if ($amount <= 0 || $amount > $account->balance) {
                throw new TransactionRejectedException('Montant invalide pour ce paiement.');
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::Payment, $amount);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $amount,
                'employee_id' => $employee->id,
                'type' => TransactionType::Payment,
                'status' => $status,
            ]);

            if ($status === TransactionStatus::Completed) {
                $account->decrement('balance', $amount);
            }

            return $transaction;
        });
    }
}
