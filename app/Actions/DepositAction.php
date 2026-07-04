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

class DepositAction
{
    public function handle(string $accountCode, float $amount, Employee $employee): Transaction
    {
        return DB::transaction(function () use ($accountCode, $amount, $employee) {
            // Verrou pessimiste : bloque toute autre transaction concurrente
            // sur ce meme compte jusqu'a la fin de ce bloc. C'est ce qui
            // corrige la race condition de l'ancien projet (increment()
            // sans verrou pouvait perdre un depot simultane).
            $account = Account::where('code', $accountCode)->lockForUpdate()->firstOrFail();

            if (! $account->is_active) {
                throw new TransactionRejectedException('Ce compte a ete desactive.');
            }

            if ($amount <= 0) {
                throw new TransactionRejectedException('Le montant doit etre superieur a 0.');
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(TransactionType::Deposit, $amount);
            $status = $requiredLevels > 0 ? TransactionStatus::Pending : TransactionStatus::Completed;

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'code' => Transaction::generateUniqueCode(),
                'amount' => $amount,
                'employee_id' => $employee->id,
                'type' => TransactionType::Deposit,
                'status' => $status,
            ]);

            // Le solde n'est mis a jour immediatement que si aucune
            // approbation n'est requise. Si une approbation est requise,
            // c'est ApproveTransactionAction (etape suivante) qui
            // appliquera le mouvement de solde, une fois validee.
            if ($status === TransactionStatus::Completed) {
                $account->increment('balance', $amount);
            }

            return $transaction;
        });
    }
}