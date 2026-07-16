<?php

// app/Filament/Pages/Auth/Login.php
namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use App\Services\LoginAuditService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        $service = app(LoginAuditService::class);
        
        $email = $this->form->getState()['email'] ?? null;
        $throttleKey = 'login:' . mb_strtolower($email) . '|' . request()->ip();

        // 1. Compte verrouillé par NOTRE système (audit + lockout progressif)
        if ($email && $service->isLocked($email)) {
            $until = $service->lockedUntil($email);
            $service->record($email, 'blocked', 'Tentative pendant verrouillage');

            Notification::make()
                ->title('Compte temporairement verrouillé')
                ->body("Réessayez après " . $until->diffForHumans())
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.email' => "Compte verrouillé jusqu'à {$until->format('H:i:s')}.",
            ]);
        }

        // 2. Rate limit AVANT d'appeler Filament, pour pouvoir logger nous-même
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $service->record($email, 'blocked', "Rate limited ({$seconds}s restantes)");

            Notification::make()
                ->title('Trop de tentatives')
                ->body("Réessayez dans {$seconds} secondes.")
                ->danger()
                ->send();

            return null;
        }

        // dans Login::authenticate(), après la vérification du lockout
        $user = \App\Models\User::where('email', $email)->first();

        if ($user && !$user->is_active) {
            $service->record($email, 'blocked', 'Compte désactivé: ' . $user->deactivation_reason, $user);

            throw ValidationException::withMessages([
                'data.email' => 'Ce compte a été désactivé. Contactez un administrateur.',
            ]);
        }

        try {
            $result = parent::authenticate();

            if (!$result) {
                // parent::authenticate() a échoué silencieusement (mauvais mdp)
                RateLimiter::hit($throttleKey, 60);
                $service->record($email, 'failed_password', 'Identifiants invalides');
                return null;
            }

            RateLimiter::clear($throttleKey);
            $user = \Illuminate\Support\Facades\Auth::user();
            
            $service->record($email, 'success', null, $user);
            app(\App\Services\ConcurrentSessionService::class)->handleNewLogin($user);


            return $result;
        } catch (ValidationException $e) {
            RateLimiter::hit($throttleKey, 60);
            $service->record($email, 'failed_password', 'Identifiants invalides');
            throw $e;
        }
    }
}