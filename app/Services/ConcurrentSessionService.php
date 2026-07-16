<?php 

// app/Services/ConcurrentSessionService.php
namespace App\Services;

use App\Models\User;
use App\Models\Core\TrustedDevice;
use App\Notifications\ConcurrentLoginDetected;
use App\Notifications\RemoteSessionTerminated;
use Illuminate\Support\Facades\DB;

class ConcurrentSessionService
{
    public function handleNewLogin(User $user): void
    {
        $currentSessionId = session()->getId();

        // 1. Chercher d'autres sessions actives pour ce user
        $otherSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->get();

        $hadConcurrentSession = $otherSessions->isNotEmpty();

        if ($hadConcurrentSession) {
            // 2. Déconnecter les anciennes sessions
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        // 3. Vérifier/enregistrer l'appareil
        $fingerprint = hash('sha256', $this->ipRange(request()->ip()) . '|' . request()->userAgent());

        $device = TrustedDevice::where('user_id', $user->id)
            ->where('fingerprint', $fingerprint)
            ->first();

        $isNewDevice = !$device;

        if ($device) {
            $device->increment('login_count');
            $device->update([
                'last_seen_at' => now(),
                'ip_address' => request()->ip(),
                'is_trusted' => $device->login_count >= 5, // auto-confiance après 5 connexions
            ]);
        } else {
            TrustedDevice::create([
                'user_id' => $user->id,
                'fingerprint' => $fingerprint,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'is_trusted' => false,
            ]);
        }

        // 4. Notifier si session concurrente OU nouvel appareil non fiable
        if ($hadConcurrentSession || $isNewDevice) {
            $this->notifyConcurrentOrNewDevice($user, $hadConcurrentSession, $isNewDevice);
        }
    }

    protected function notifyConcurrentOrNewDevice(User $user, bool $concurrent, bool $newDevice): void
    {
        // Email à l'utilisateur
        $user->notify(new ConcurrentLoginDetected(
            ip: request()->ip(),
            userAgent: request()->userAgent(),
            wasConcurrent: $concurrent,
            isNewDevice: $newDevice,
        ));

        // Alerte aux admins (Filament database notification)
        $admins = User::role('super_admin')->get(); // adapte à ton système Spatie
        foreach ($admins as $admin) {
            \Filament\Notifications\Notification::make()
                ->title('Connexion suspecte détectée')
                ->body("Utilisateur: {$user->email} — " .
                    ($concurrent ? 'Session concurrente déconnectée. ' : '') .
                    ($newDevice ? 'Nouvel appareil non reconnu.' : ''))
                ->warning()
                ->sendToDatabase($admin);
        }
    }

    /** Regroupe les IP par /24 pour tolérer les IP dynamiques du même réseau */
    protected function ipRange(string $ip): string
    {
        $parts = explode('.', $ip);
        return count($parts) === 4 ? "{$parts[0]}.{$parts[1]}.{$parts[2]}.0/24" : $ip;
    }
}