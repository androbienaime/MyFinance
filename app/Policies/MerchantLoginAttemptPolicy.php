<?php

namespace App\Policies;

use App\Models\Core\MerchantLoginAttempt;
use App\Models\User;

class MerchantLoginAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('merchant_login_attempts.view_any');
    }

    public function view(User $user, MerchantLoginAttempt $attempt): bool
    {
        return $user->can('merchant_login_attempts.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MerchantLoginAttempt $attempt): bool
    {
        return false;
    }

    public function delete(User $user, MerchantLoginAttempt $attempt): bool
    {
        return false;
    }
}