<?php

namespace App\Actions;

use App\Enums\TransactionDirection;
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
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($transaction->transfer_group_id && $transaction->direction === TransactionDirection::Credit) {
                throw new TransactionRejectedException(
                    'Supprimez ce virement depuis sa transaction source (debit), pas depuis la reception.'
                );
            }

            $account = $transaction->account()->lockForUpdate()->first();

            $siblingLegs = collect();

            if ($transaction->transfer_group_id) {
                $siblingLegs = Transaction::where('transfer_group_id', $transaction->transfer_group_id)
                    ->where('id', '!=', $transaction->id)
                    ->lockForUpdate()
                    ->get()
                    ->map(function (Transaction $leg) {
                        $leg->setRelation('account', $leg->account()->lockForUpdate()->first());
                        return $leg;
                    });
            }

            if ($transaction->status === TransactionStatus::Completed) {
                $this->reverseBalanceMovement($transaction, $account, $siblingLegs);

                if ($transaction->type === TransactionType::Deposit) {
                    $transaction->tagsPayments()->delete();
                }
            }

            $transaction->update([
                'deleted_by' => $actor->id,
                'deletion_reason' => $reason,
            ]);
            $transaction->delete();

            $siblingLegs->each(function (Transaction $leg) use ($actor, $reason, $transaction) {
                $leg->update([
                    'deleted_by' => $actor->id,
                    'deletion_reason' => "Supprime avec la jambe liee (groupe {$transaction->transfer_group_id}) : {$reason}",
                ]);
                $leg->delete();
            });
        });
    }

    private function reverseBalanceMovement(Transaction $transaction, $account, $siblingLegs): void
    {
        match ($transaction->type) {
            TransactionType::Deposit => $account->decrement('balance', $transaction->amount),

            TransactionType::Withdrawal,
            TransactionType::AccountSettlement => $account->increment('balance', $transaction->amount),

            TransactionType::Transfer => tap(true, function () use ($account, $siblingLegs, $transaction) {
                $account->increment('balance', $transaction->amount);

                $siblingLegs->each(function (Transaction $leg) {
                    match ($leg->direction) {
                        TransactionDirection::Credit => $leg->account->decrement('balance', $leg->amount),
                        TransactionDirection::Debit => $leg->account->increment('balance', $leg->amount),
                        null => null,
                    };
                });
            }),

            TransactionType::TransferFee => null,

            TransactionType::AccountRestoration => null,
            TransactionType::AccountClosure => null,
        };
    }
}