<?php

namespace App\Actions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Employee;
use App\Models\Core\Transaction;
use Illuminate\Support\Facades\DB;

class DeleteTransactionAction
{
    public function handle(Transaction $transaction, Employee $actor, string $reason): void
    {
        DB::transaction(function () use ($transaction, $actor, $reason) {
            // Verrouille la transaction ET son compte - meme principe que
            // ApproveTransactionAction, pour eviter qu'une suppression et
            // une approbation concurrentes ne se marchent dessus.
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $account = $transaction->account()->lockForUpdate()->first();

            if ($transaction->status === TransactionStatus::Completed) {
                // Le solde avait deja bouge : on inverse exactement le
                // mouvement applique par DepositAction/WithdrawAction/
                // PaymentAction/ApproveTransactionAction.
                match ($transaction->type) {
                    TransactionType::Deposit => $account->decrement('balance', $transaction->amount),
                    TransactionType::Withdrawal, TransactionType::AccountSettlement => $account->increment('balance', $transaction->amount),
                };

                // Un depot a cases complete avait fige des numeros de case
                // comme "payes" - les liberer pour qu'ils redeviennent
                // disponibles, puisque le paiement qui les justifiait
                // n'existe plus.
                if ($transaction->type === TransactionType::Deposit) {
                    $transaction->tagsPayments()->delete();
                }
            }

            $transaction->update([
                'deleted_by' => $actor->id,
                'deletion_reason' => $reason,
            ]);

            // SoftDeletes : la ligne reste en base (deleted_at rempli),
            // avec deleted_by/deletion_reason juste au-dessus pour
            // l'audit complet - jamais de suppression definitive d'une
            // transaction financiere.
            $transaction->delete();
        });
    }
}