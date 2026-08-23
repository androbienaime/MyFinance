<?php

namespace App\Policies;

use App\Models\Core\Account;
use App\Models\User;
use Illuminate\Validation\Rules\Can;

class AccountPolicy
{
    private const NON_BYPASSABLE = ['update'];

    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, self::NON_BYPASSABLE, true)) {
            return null; // laisse la methode dediee statuer
        }

        return $user->isHeadOffice() ? true : null;
    }


    public function viewAny(User $user): bool
    {
        return $user->can('accounts.view');
    }

    public function view(User $user, Account $account): bool
    {
        return $user->can('accounts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('accounts.create');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->can('accounts.update'); // Les comptes ne sont pas modifiables par les utilisateurs, seulement par le systeme (ex: via des actions de credit/debit)
    }

    /**
     * Desactiver un compte est distinct de le modifier : action
     * sensible, permission dediee plutot que noyee dans "update".
     */
    public function toggleActive(User $user, Account $account): bool
    {
        return $user->can('accounts.toggle-active');
    }

    public function delete(User $user, Account $account): bool
    {
        return false;
    }
}