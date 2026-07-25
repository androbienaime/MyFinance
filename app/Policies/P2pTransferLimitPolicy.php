<?php

namespace App\Policies;

use App\Models\Core\P2pTransferLimit;
use App\Models\User;

class P2pTransferLimitPolicy
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
        return $user->can('p2p_transfer_limits.view_any');
    }

    public function view(User $user, P2pTransferLimit $p2pTransferLimit): bool
    {
        return $user->can('p2p_transfer_limits.view');
    }

    public function create(User $user): bool
    {
        return $user->can('p2p_transfer_limits.create');
    }

    public function update(User $user, P2pTransferLimit $p2pTransferLimit): bool
    {
        return $user->can('p2p_transfer_limits.update');
    }

    public function delete(User $user, P2pTransferLimit $p2pTransferLimit): bool
    {
        return $user->can('p2p_transfer_limits.delete');
    }
}
