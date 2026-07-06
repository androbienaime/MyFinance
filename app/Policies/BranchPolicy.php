<?php

namespace App\Policies;

use App\Models\Core\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Pas de before() ici, volontairement : contrairement aux autres
     * Policies, on ne veut PAS qu'une permission generique puisse
     * jamais donner acces a la gestion des succursales elles-memes.
     * Seul isHeadOffice() (permission system.full-access) y a droit,
     * verifie explicitement dans chaque methode.
     */
    public function viewAny(User $user): bool
    {
        return $user->isHeadOffice();
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->isHeadOffice();
    }

    public function create(User $user): bool
    {
        return $user->isHeadOffice();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->isHeadOffice();
    }

    public function delete(User $user, Branch $branch): bool
    {
        return false;
    }
}