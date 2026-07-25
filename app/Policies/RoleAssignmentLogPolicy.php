<?php

namespace App\Policies;

use App\Models\Core\RoleAssignmentLog;
use App\Models\User;

class RoleAssignmentLogPolicy
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
        return $user->can('role_assignment_logs.view');
    }

    public function view(User $user, RoleAssignmentLog $roleAssignmentLog): bool
    {
        return $user->can('role_assignment_logs.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RoleAssignmentLog $roleAssignmentLog): bool
    {
        return false;
    }

    public function delete(User $user, RoleAssignmentLog $roleAssignmentLog): bool
    {
        return false;
    }
}
