<?php

namespace App\Policies;

use App\Models\Core\P2pTransferFeeTier;
use App\Models\User;

class P2pTransferFeeTierPolicy
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
        return $user->can('p2p_transfer_fee_tiers.view_any');
    }

    public function view(User $user, P2pTransferFeeTier $p2pTransferFeeTier): bool
    {
        return $user->can('p2p_transfer_fee_tiers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('p2p_transfer_fee_tiers.create');
    }

    public function update(User $user, P2pTransferFeeTier $p2pTransferFeeTier): bool
    {
        return $user->can('p2p_transfer_fee_tiers.update');
    }

    public function delete(User $user, P2pTransferFeeTier $p2pTransferFeeTier): bool
    {
        return $user->can('p2p_transfer_fee_tiers.delete');
    }
}
