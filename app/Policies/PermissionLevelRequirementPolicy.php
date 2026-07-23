<?php

namespace App\Policies;

use App\Models\Core\PermissionLevelRequirement;
use App\Models\User;

class PermissionLevelRequirementPolicy
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
        return $user->can('permission_level_requirements.view');
    }

    public function view(User $user, PermissionLevelRequirement $permissionLevelRequirement): bool
    {
        return $user->can('permission_level_requirements.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PermissionLevelRequirement $permissionLevelRequirement): bool
    {
        return $user->can('permission_level_requirements.update');
    }

    public function delete(User $user, PermissionLevelRequirement $permissionLevelRequirement): bool
    {
        return false;
    }
}
