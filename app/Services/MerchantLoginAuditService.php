<?php

namespace App\Services;

use App\Models\Core\MerchantAccountLockout;
use App\Models\Core\MerchantLoginAttempt;
use App\Models\Core\MerchantProfile;
use Carbon\Carbon;

class MerchantLoginAuditService
{
    protected const MAX_ATTEMPTS_SOFT = 5;
    protected const MAX_ATTEMPTS_HARD = 10;
    protected const MAX_ATTEMPTS_CRITICAL = 15;

    public function record(string $identifier, string $status, ?MerchantProfile $merchant = null): MerchantLoginAttempt
    {
        $attempt = MerchantLoginAttempt::create([
            'identifier' => $identifier,
            'merchant_profile_id' => $merchant?->id,
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
        $lockout = MerchantAccountLockout::firstOrNew(['identifier' => $identifier]);
        $lockout->failed_count = ($lockout->failed_count ?? 0) + 1;
        $lockout->last_attempt_at = now();

        $lockout->locked_until = match (true) {
            $lockout->failed_count >= self::MAX_ATTEMPTS_CRITICAL => now()->addHours(24),
            $lockout->failed_count >= self::MAX_ATTEMPTS_HARD => now()->addMinutes(30),
            $lockout->failed_count >= self::MAX_ATTEMPTS_SOFT => now()->addMinutes(2),
            default => $lockout->locked_until,
        };

        $lockout->save();
    }

    public function isLocked(string $identifier): bool
    {
        $lockout = MerchantAccountLockout::where('identifier', $identifier)->first();

        return $lockout?->isCurrentlyLocked() ?? false;
    }

    public function lockedUntil(string $identifier): ?Carbon
    {
        return MerchantAccountLockout::where('identifier', $identifier)->value('locked_until');
    }

    protected function resetLockout(string $identifier): void
    {
        MerchantAccountLockout::where('identifier', $identifier)->delete();
    }
}