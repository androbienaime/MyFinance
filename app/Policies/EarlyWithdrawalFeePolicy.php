<?php

namespace App\Policies;

use App\Models\Core\EarlyWithdrawalFee;
use App\Models\User;

class EarlyWithdrawalFeePolicy
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
        return $user->can('early_withdrawal_fees.view_any');
    }

    public function view(User $user, EarlyWithdrawalFee $earlyWithdrawalFee): bool
    {
        return $user->can('early_withdrawal_fees.view');
    }

    public function create(User $user): bool
    {
        return $user->can('early_withdrawal_fees.create');
    }

    public function update(User $user, EarlyWithdrawalFee $earlyWithdrawalFee): bool
    {
        return $user->can('early_withdrawal_fees.update');
    }

    public function delete(User $user, EarlyWithdrawalFee $earlyWithdrawalFee): bool
    {
        return $user->can('early_withdrawal_fees.delete');
    }
}
