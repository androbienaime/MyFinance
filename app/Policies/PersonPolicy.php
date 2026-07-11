<?php

namespace App\Policies;

use App\Models\Core\Person;
use App\Models\User;

class PersonPolicy
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
        return $user->can('people.view');
    }

    public function view(User $user, Person $person): bool
    {
        return $user->can('people.view');
    }

    public function create(User $user): bool
    {
        return $user->can('people.create');
    }

    public function update(User $user, Person $person): bool
    {
        return $user->can('people.update');
    }

    /**
     * Desactiver un compte est distinct de le modifier : action
     * sensible, permission dediee plutot que noyee dans "update".
     */
    public function toggleActive(User $user, Person $person): bool
    {
        return $user->can('people.toggle-active');
    }

    public function delete(User $user, Person $person): bool
    {
        return false;
    }
}
