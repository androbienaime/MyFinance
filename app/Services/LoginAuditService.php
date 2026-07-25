<?php

namespace App\Services;

use App\Models\Core\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoginAuditService
{
    public function record(string $email, string $status, ?string $reason = null, ?User $user = null): LoginAttempt
    {
        $attempt = LoginAttempt::create([
            'email' => $email,
            'user_id' => $user?->id,
            'branch_id' => $user?->current_branch_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $status,
            'failure_reason' => $reason,
            'attempted_at' => now(),
        ]);

        if ($status === 'success') {
            $this->resetLockout($email);
            $this->detectSuspiciousSuccess($email, $user);
        } else {
            $this->registerFailure($email);
        }

        return $attempt;
    }

    protected function registerFailure(string $email): void
    {
        DB::table('account_lockouts')->updateOrInsert(
            ['email' => $email],
            [
                'failed_count' => DB::raw('failed_count + 1'),
                'last_attempt_at' => now(),
                'updated_at' => now(),
            ]
        );

        $lockout = DB::table('account_lockouts')->where('email', $email)->first();
        $count = $lockout->failed_count ?? 1;

        $softThreshold = (int) setting('security.max_login_attempts_soft');
        $hardThreshold = (int) setting('security.max_login_attempts_hard');
        $criticalThreshold = (int) setting('security.max_login_attempts_critical');

        $softMinutes = (int) setting('security.lockout_duration_soft_minutes');
        $hardMinutes = (int) setting('security.lockout_duration_hard_minutes');

        $lockUntil = match (true) {
            $count >= $criticalThreshold => now()->addYears(100), // verrouillage manuel
            $count >= $hardThreshold => now()->addMinutes($hardMinutes),
            $count >= $softThreshold => now()->addMinutes($softMinutes),
            default => null,
        };

        if ($lockUntil) {
            DB::table('account_lockouts')
                ->where('email', $email)
                ->update(['locked_until' => $lockUntil]);
        }

        if ($count === $criticalThreshold) {
            $this->alertAdmins($email, 'Compte verrouillé après ' . $criticalThreshold . ' échecs — intervention requise.');
        }
    }

    public function isLocked(string $email): bool
    {
        $lockout = DB::table('account_lockouts')->where('email', $email)->first();

        if (!$lockout || !$lockout->locked_until) {
            return false;
        }

        return Carbon::parse($lockout->locked_until)->isFuture();
    }

    public function lockedUntil(string $email): ?Carbon
    {
        $lockout = DB::table('account_lockouts')->where('email', $email)->first();
        return $lockout?->locked_until ? Carbon::parse($lockout->locked_until) : null;
    }

    protected function resetLockout(string $email): void
    {
        DB::table('account_lockouts')->where('email', $email)->delete();
    }

    protected function detectSuspiciousSuccess(string $email, ?User $user): void
    {
        if (!$user) return;

        $recentFailures = LoginAttempt::where('email', $email)
            ->where('status', '!=', 'success')
            ->where('attempted_at', '>=', now()->subMinutes(30))
            ->count();

        if ($recentFailures >= 3) {
            $this->alertAdmins($email, "Connexion réussie après {$recentFailures} échecs récents — possible compromission.");
        }

        $knownIp = LoginAttempt::where('user_id', $user->id)
            ->where('status', 'success')
            ->where('ip_address', request()->ip())
            ->where('id', '!=', LoginAttempt::latest()->first()?->id)
            ->exists();

        if (!$knownIp) {
            $this->alertAdmins($email, "Connexion depuis une nouvelle IP (" . request()->ip() . ") pour ce compte.");
        }
    }

    protected function alertAdmins(string $email, string $message): void
    {
        \Illuminate\Support\Facades\Log::channel('security')->warning($message, ['email' => $email]);
    }
}