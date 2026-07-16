<?php

// app/Http/Middleware/RequirePasswordChange.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $panelId = Filament::getCurrentPanel()?->getId();

        $exemptRoutes = [
            "filament.{$panelId}.pages.force-password-change",
            "filament.{$panelId}.auth.logout",
        ];

        if ($user?->must_change_password && !$request->routeIs($exemptRoutes)) {
            return redirect()->route("filament.{$panelId}.pages.force-password-change");
        }

        return $next($request);
    }
}