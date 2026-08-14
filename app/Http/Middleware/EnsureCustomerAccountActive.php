<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCustomerAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $customer = $request->user('customer');

        if (! $customer || ! $customer->is_active) {
            $customer?->currentAccessToken()?->delete();

            return response()->json(['message' => 'Compte desactive.'], 403);
        }

        return $next($request);
    }
}