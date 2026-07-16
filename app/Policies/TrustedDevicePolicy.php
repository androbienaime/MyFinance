<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Core\TrustedDevice;

class TrustedDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('trusted_devices.view');
    }

    public function view(User $user, TrustedDevice $device): bool
    {
        return $user->can('trusted_devices.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TrustedDevice $device): bool
    {
        return $user->can('trusted_devices.trust');
    }

    public function delete(User $user, TrustedDevice $device): bool
    {
        return $user->can('trusted_devices.revoke');
    }
}