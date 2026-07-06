<?php

namespace App\Policies;

use App\Models\Core\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isHeadOffice() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.update');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return false;
    }
}