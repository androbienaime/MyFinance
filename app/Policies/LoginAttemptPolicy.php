<?php
// app/Policies/LoginAttemptPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\Core\LoginAttempt;

class LoginAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('login_attempts.view');
    }

    public function view(User $user, LoginAttempt $attempt): bool
    {
        return $user->can('login_attempts.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LoginAttempt $attempt): bool
    {
        return false;
    }

    public function delete(User $user, LoginAttempt $attempt): bool
    {
        return false;
    }
}