<?php

namespace App\Actions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\ApprovalThreshold;
use App\Models\Core\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveTransactionAction
{
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
            // Recharge et verrouille la transaction ET son compte pour
            // eviter qu'une double soumission (deux clics, deux onglets)
            // ne fasse appliquer le mouvement de solde deux fois.
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $account = $transaction->account()->lockForUpdate()->first();

            if ($transaction->status !== TransactionStatus::Pending) {
                throw new TransactionRejectedException('Cette transaction n\'est plus en attente d\'approbation.');
            }

            // Separation des taches (four-eyes principle) : personne ne
            // peut valider sa propre transaction, meme avec la bonne
            // permission. Regle non contournable par la Policy.
            if ($transaction->employee?->user_id === $approver->id) {
                throw new TransactionRejectedException('Vous ne pouvez pas approuver votre propre transaction.');
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

                return $transaction;
            }

            $requiredLevels = ApprovalThreshold::levelsRequiredFor(
                TransactionType::from($transaction->type->value),
                (float) $transaction->amount
            );

            $approvedCount = $transaction->approvals()->where('decision', 'approved')->count();

            if ($approvedCount < $requiredLevels) {
                // Pas encore assez de niveaux : on marque "Approved" pour
                // tracer la progression, mais le solde ne bouge pas encore.
                $transaction->update(['status' => TransactionStatus::Approved]);

                return $transaction;
            }

            // Dernier niveau atteint : on applique enfin le mouvement de
            // solde qui avait ete mis en attente dans WithdrawAction/PaymentAction.
            match ($transaction->type) {
                TransactionType::Deposit => $account->increment('balance', $transaction->amount),
                TransactionType::Withdrawal, TransactionType::Payment => $account->decrement('balance', $transaction->amount),
            };

            $transaction->update(['status' => TransactionStatus::Completed]);

            return $transaction;
        });
    }
}


