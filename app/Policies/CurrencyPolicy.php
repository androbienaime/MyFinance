<?php

namespace App\Policies;

use App\Models\Core\Currency;
use App\Models\User;

class CurrencyPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

     public function viewAny(User $user): bool
    {
        return $user->can('currencies.view_any');
    }

    public function view(User $user, Currency $currency): bool
    {
        return $user->can('currencies.view');
    }

    public function create(User $user): bool
    {
        return $user->can('currencies.create');
    }

    public function update(User $user, Currency $currency): bool
    {
        return $user->can('currencies.update');
    }

    public function delete(User $user, Currency $currency): bool
    {
        return false;
    }
}
