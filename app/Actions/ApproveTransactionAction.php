<?php

namespace App\Actions;

use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\ApprovalThreshold;
use App\Models\Core\Transaction;
use App\Models\User;
use App\Services\TransferNotifier;
use Illuminate\Support\Facades\DB;

class ApproveTransactionAction
{
    public function __construct(private TransferNotifier $notifier) {}

    public function approve(Transaction $transaction, User $approver, ?string $comment = null): Transaction
    {
        return $this->decide($transaction, $approver, approved: true, comment: $comment);
    }

    public function reject(Transaction $transaction, User $approver, ?string $comment = null): Transaction
    {
        return $this->decide($transaction, $approver, approved: false, comment: $comment);
    }

    private function decide(Transaction $transaction, User $approver, bool $approved, ?string $comment): Transaction
    {
        return DB::transaction(function () use ($transaction, $approver, $approved, $comment) {
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($transaction->transfer_group_id && $transaction->direction === TransactionDirection::Credit) {
                throw new TransactionRejectedException(
                    'Approuvez ce virement depuis sa transaction source (debit), pas depuis la reception.'
                );
            }

            if ($transaction->status !== TransactionStatus::Pending) {
                throw new TransactionRejectedException('Cette transaction n\'est plus en attente d\'approbation.');
            }

            if ($transaction->employee?->user_id === $approver->id) {
                throw new TransactionRejectedException('Vous ne pouvez pas approuver votre propre transaction.');
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

            $level = $transaction->approvals()->where('decision', 'approved')->count() + 1;

            $transaction->approvals()->create([
                'approved_by' => $approver->id,
                'level' => $level,
                'decision' => $approved ? 'approved' : 'rejected',
                'comment' => $comment,
            ]);

            if (! $approved) {
                $transaction->update(['status' => TransactionStatus::Rejected]);
                $siblingLegs->each(fn (Transaction $leg) => $leg->update(['status' => TransactionStatus::Rejected]));

                return $transaction;
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(
                TransactionType::from($transaction->type->value),
                (float) $transaction->amount
            );

            $approvedCount = $transaction->approvals()->where('decision', 'approved')->count();

            if ($approvedCount < $requiredLevels) {
                $transaction->update(['status' => TransactionStatus::Approved]);
                $siblingLegs->each(fn (Transaction $leg) => $leg->update(['status' => TransactionStatus::Approved]));

                return $transaction;
            }

            $this->applyBalanceMovement($transaction, $account, $siblingLegs);

            $transaction->update(['status' => TransactionStatus::Completed]);
            $siblingLegs->each(fn (Transaction $leg) => $leg->update(['status' => TransactionStatus::Completed]));

            if ($transaction->type === TransactionType::Transfer) {
                $creditPrincipal = $siblingLegs->first(fn (Transaction $leg) => $leg->direction === TransactionDirection::Credit);
                $feeAmount = optional($siblingLegs->first(fn (Transaction $leg) => $leg->type === TransactionType::TransferFee))->amount ?? 0;

                if ($creditPrincipal) {
                    $this->notifier->notifyTransferCompleted($account, $creditPrincipal->account, (float) $transaction->amount, (float) $feeAmount);
                }
            }

            return $transaction;
        });
    }

    private function applyBalanceMovement(Transaction $transaction, Account $account, $siblingLegs): void
    {
        match ($transaction->type) {
            TransactionType::Deposit => $account->increment('balance', $transaction->amount),

            TransactionType::Withdrawal,
            TransactionType::AccountSettlement => $account->decrement('balance', $transaction->amount),

            TransactionType::Transfer => tap(true, function () use ($account, $siblingLegs, $transaction) {
                $account->decrement('balance', $transaction->amount);

                $siblingLegs->each(function (Transaction $leg) {
                    match ($leg->direction) {
                        TransactionDirection::Credit => $leg->account->increment('balance', $leg->amount),
                        TransactionDirection::Debit => $leg->account->decrement('balance', $leg->amount),
                        null => null,
                    };
                });
            }),

            // Les jambes de frais suivent toujours le statut de la jambe
            // principale et sont deja traitees dans le cas Transfer
            // ci-dessus (elles font partie de $siblingLegs).
            TransactionType::TransferFee => null,

            TransactionType::AccountRestoration => $account->update([
                'balance' => $transaction->amount,
                'is_active' => true,
            ]),

            // Aucune Transaction n'est actuellement creee avec ce type via
            // le pipeline d'approbation (Account::closeAccount() cree un
            // AccountClosure directement) - present pour l'exhaustivite.
            TransactionType::AccountClosure => null,
        };
    }
}