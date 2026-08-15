<?php

namespace App\Policies;

use App\Models\Core\QrPaymentFeeTier;
use App\Models\User;

class QrPaymentFeeTierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('qr_payment_fee_tiers.view_any');
    }

    public function view(User $user, QrPaymentFeeTier $tier): bool
    {
        return $user->can('qr_payment_fee_tiers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('qr_payment_fee_tiers.create');
    }

    public function update(User $user, QrPaymentFeeTier $tier): bool
    {
        return $user->can('qr_payment_fee_tiers.update');
    }

    public function delete(User $user, QrPaymentFeeTier $tier): bool
    {
        return $user->can('qr_payment_fee_tiers.delete');
    }
}