<?php

// app/Services/LoginAuditService.php
namespace App\Services;

use App\Models\Core\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class LoginAuditService
{
    protected const MAX_ATTEMPTS_SOFT = 5;   // -> lock 1 min
    protected const MAX_ATTEMPTS_HARD = 10;  // -> lock 15 min
    protected const MAX_ATTEMPTS_CRITICAL = 15; // -> lock manuel admin

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

        $lockUntil = match (true) {
            $count >= self::MAX_ATTEMPTS_CRITICAL => now()->addYears(100), // verrouillage manuel
            $count >= self::MAX_ATTEMPTS_HARD => now()->addMinutes(15),
            $count >= self::MAX_ATTEMPTS_SOFT => now()->addMinute(),
            default => null,
        };

        if ($lockUntil) {
            DB::table('account_lockouts')
                ->where('email', $email)
                ->update(['locked_until' => $lockUntil]);
        }

        if ($count === self::MAX_ATTEMPTS_CRITICAL) {
            $this->alertAdmins($email, 'Compte verrouillé après 15 échecs — intervention requise.');
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

        // Échecs suivis d'un succès = pattern brute-force réussi potentiel
        $recentFailures = LoginAttempt::where('email', $email)
            ->where('status', '!=', 'success')
            ->where('attempted_at', '>=', now()->subMinutes(30))
            ->count();

        if ($recentFailures >= 3) {
            $this->alertAdmins($email, "Connexion réussie après {$recentFailures} échecs récents — possible compromission.");
        }

        // Nouvelle IP jamais vue pour ce compte
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
        // Adapte à ta notification interne (Filament Notification, email, etc.)
        \Illuminate\Support\Facades\Log::channel('security')->warning($message, ['email' => $email]);
        // Notification::send($admins, new SuspiciousLoginNotification($email, $message));
    }
}