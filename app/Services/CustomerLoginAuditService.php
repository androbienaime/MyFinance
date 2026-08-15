<?php

namespace App\Services;

use App\Models\Core\Customer;
use App\Models\Core\CustomerAccountLockout;
use App\Models\Core\CustomerLoginAttempt;
use Carbon\Carbon;

class CustomerLoginAuditService
{
    protected const MAX_ATTEMPTS_SOFT = 5;
    protected const MAX_ATTEMPTS_HARD = 10;
    protected const MAX_ATTEMPTS_CRITICAL = 15;

    public function record(string $identifier, string $status, ?Customer $customer = null): CustomerLoginAttempt
    {
        $attempt = CustomerLoginAttempt::create([
            'identifier' => $identifier,
            'customer_id' => $customer?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $status,
            'attempted_at' => now(),
        ]);

        if ($status === 'success') {
            $this->resetLockout($identifier);
        } else {
            $this->registerFailure($identifier);
        }

        return $attempt;
    }

    protected function registerFailure(string $identifier): void
    {
        $lockout = CustomerAccountLockout::firstOrNew(['identifier' => $identifier]);
        $lockout->failed_count = ($lockout->failed_count ?? 0) + 1;
        $lockout->last_attempt_at = now();

        $lockout->locked_until = match (true) {
            $lockout->failed_count >= self::MAX_ATTEMPTS_CRITICAL => now()->addHours(24),
            $lockout->failed_count >= self::MAX_ATTEMPTS_HARD => now()->addMinutes(30),
            $lockout->failed_count >= self::MAX_ATTEMPTS_SOFT => now()->addMinutes(2),
            default => $lockout->locked_until, // conserve un verrou deja pose tant que le seuil suivant n'est pas atteint
        };

        $lockout->save();
    }

    public function isLocked(string $identifier): bool
    {
        $lockout = CustomerAccountLockout::where('identifier', $identifier)->first();

        return $lockout?->isCurrentlyLocked() ?? false;
    }

    public function lockedUntil(string $identifier): ?Carbon
    {
        return CustomerAccountLockout::where('identifier', $identifier)->value('locked_until');
    }

    protected function resetLockout(string $identifier): void
    {
        CustomerAccountLockout::where('identifier', $identifier)->delete();
    }
}