<?php

namespace App\Policies;

use App\Models\Core\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Le siege central (permission system.full-access) a acces total.
     * Toute autre regle ci-dessous ne s'applique qu'aux roles de succursale.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isHeadOffice() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('transactions.view');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->can('transactions.view');
    }

    public function createDeposit(User $user): bool
    {
        return $user->can('transactions.deposit');
    }

    public function createWithdrawal(User $user): bool
    {
        return $user->can('transactions.withdraw');
    }

    public function createPayment(User $user): bool
    {
        return $user->can('transactions.payment');
    }
    
    public function createSettlement(User $user): bool
    {
        return $user->can('transactions.settlement');
    }

    /**
     * Regle deja verrouillee en dur dans ApproveTransactionAction
     * (anti auto-approbation), ici on ajoute la couche permission
     * classique par-dessus.
     */
    public function approve(User $user, Transaction $transaction): bool
    {
        if ($transaction->employee?->user_id === $user->id) {
            return false;
        }

        return $user->can('transactions.approve');
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        // Contrairement aux autres methodes de cette Policy, pas de simple
        // permission ici : uniquement le siege, sans exception - annuler un
        // mouvement d'argent deja effectue est l'action la plus sensible
        // de tout le systeme.
        return $user->isHeadOffice();
    }

    public function createTransfer(User $user): bool
    {
        return $user->can('transactions.transfer');
    }
}