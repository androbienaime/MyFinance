<?php

namespace App\Policies;

use App\Models\Core\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isHeadOffice() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('employees.view');
    }

    /**
     * Contrairement a Customer/Transaction (avant() suffit avec un tri),
     * ici on verifie explicitement l'appartenance a la succursale :
     * un Director ne doit jamais pouvoir voir/modifier la fiche d'un
     * employe d'une autre succursale, meme en devinant l'URL /employees/{id}.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employees.view')
            && $employee->branch_id === $user->currentBranchId();
    }

    public function create(User $user): bool
    {
        return $user->can('employees.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.update')
            && $employee->branch_id === $user->currentBranchId();
    }

    public function delete(User $user, Employee $employee): bool
    {
        return false;
    }
}