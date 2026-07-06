<?php

namespace App\Policies;

use App\Models\Core\Account;
use App\Models\User;

class AccountPolicy
{
    public function before(User $user, string $ability): ?bool
    {
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
        return $user->can('accounts.update');
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