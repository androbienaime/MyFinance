<?php

namespace App\Policies;

use App\Models\Core\SystemUpdate;
use App\Models\User;

class SystemUpdatesPolicy
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
       return $user->can('system_updates.view_any');
    }

    public function view(User $user, SystemUpdate $systemUpdate): bool
    {
        return $user->can('system_updates.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SystemUpdate $systemUpdate): bool
    {
        return $user->can('system_updates.update');
    }

    public function delete(User $user, SystemUpdate $systemUpdate): bool
    {
        return $user->can('system_updates.delete');
    }
}
