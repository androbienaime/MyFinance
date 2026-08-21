<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutOnInactivity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('web')->check()) {
            $timeoutMinutes = (int) setting('security.inactivity_timeout_minutes', default: 10);
            $lastActivity = $request->session()->get('last_activity_at');

            if ($lastActivity && now()->diffInMinutes($lastActivity, absolute: true) >= $timeoutMinutes) {
                $userId = Auth::guard('web')->id();

                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                \Illuminate\Support\Facades\Log::channel('security')->info(
                    "Déconnexion automatique pour inactivité (utilisateur #{$userId}, délai : {$timeoutMinutes} min)."
                );

                return redirect()
                    ->route('filament.' . Filament::getCurrentPanel()?->getId() . '.auth.login')
                    ->with('inactivity_logout', true);
            }

            $request->session()->put('last_activity_at', now());
        }

        return $next($request);
    }
}