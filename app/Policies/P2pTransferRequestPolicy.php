<?php

namespace App\Policies;

use App\Models\Core\P2pTransferRequest;
use App\Models\User;

class P2pTransferRequestPolicy
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
        return $user->can('p2p_transfer_requests.view_any');
    }

    public function view(User $user, P2pTransferRequest $p2pTransferRequest): bool
    {
        return $user->can('p2p_transfer_requests.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, P2pTransferRequest $p2pTransferRequest): bool
    {
        return false;
    }

    public function delete(User $user, P2pTransferRequest $p2pTransferRequest): bool
    {
        return false;
    }
    
}
