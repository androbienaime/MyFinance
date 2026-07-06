<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Core\TypeOfAccount;

class TypeOfAccountResourcePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
    
    public function before(User $user, string $ability): ?bool
    {
        return $user->isHeadOffice() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('types_of_accounts.view');
    }

    public function view(User $user, TypeOfAccount $typeOfAccount): bool
    {
        return $user->can('types_of_accounts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('types_of_accounts.create');
    }

    public function update(User $user, TypeOfAccount $typeOfAccount): bool
    {
        return $user->can('types_of_accounts.update');
    }

    public function delete(User $user, TypeOfAccount $typeOfAccount): bool
    {
        return false;
    }
    
}
