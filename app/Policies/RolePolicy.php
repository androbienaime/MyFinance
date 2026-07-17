<?php 

namespace App\Policies;

use App\Models\User;
use App\Models\Core\Role;

class RolePolicy
{
    

    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('roles.view_any');
    }

    public function view(User $user, Role $role): bool
    {
        if (!$user->can('roles.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $userMaxLevel = $user->roles()->max('level') ?? 0;
        $isOwnRole = $user->roles()->where('roles.id', $role->id)->exists();

        return $role->level < $userMaxLevel || $isOwnRole;
    }

    public function create(User $user): bool
    {
        return $user->can('roles.assign');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->isProtected($role)) {
            return false;
        }

        // Volontairement STRICT (<) — pas d'exception pour son propre rôle,
        // donc édition bloquée même en cas de "voir en lecture seule".
        $userMaxLevel = $user->roles()->max('level') ?? 0;

        return $user->can('roles.assign') && ($role->level < $userMaxLevel || $user->isSuperAdmin());
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->isProtected($role)) {
            return false;
        }

        $userMaxLevel = $user->roles()->max('level') ?? 0;

        return $user->can('roles.assign') && ($role->level < $userMaxLevel || $user->isSuperAdmin());
    }
}