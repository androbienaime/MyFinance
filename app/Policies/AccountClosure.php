<?php

namespace App\Policies;

use App\Models\User;

class AccountClosure
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {

    }
    
    public function before(User $user, string $ability): ?bool
    {
        return $user->isHeadOffice() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('account_closures.view');
    }

    public function view(User $user, AccountClosure $account): bool
    {
        return $user->can('account_closures.view');
    }

    public function create(User $user): bool
    {
        return $user->can('account_closures.create');
    }

    public function update(User $user, AccountClosure $account): bool
    {
        return $user->can('account_closures.update');
    }

    /**
     * Desactiver un compte est distinct de le modifier : action
     * sensible, permission dediee plutot que noyee dans "update".
     */
    public function toggleActive(User $user, AccountClosure $account): bool
    {
        return $user->can('account_closures.toggle-active');
    }

    public function delete(User $user, AccountClosure $account): bool
    {
        return false;
    }
    
}
