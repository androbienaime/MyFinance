<?php

// app/Policies/MerchantProfilePolicy.php
namespace App\Policies;

use App\Models\Core\MerchantProfile;
use App\Models\User;

class MerchantProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('merchant_profiles.view_any');
    }

    public function view(User $user, MerchantProfile $profile): bool
    {
        return $user->can('merchant_profiles.view');
    }

    public function update(User $user, MerchantProfile $profile): bool
    {
        return $user->can('merchant_profiles.update');
    }

    public function approve(User $user, MerchantProfile $profile): bool
    {
        return $user->can('merchant_profiles.approve');
    }

    public function suspend(User $user, MerchantProfile $profile): bool
    {
        return $user->can('merchant_profiles.suspend');
    }
}
