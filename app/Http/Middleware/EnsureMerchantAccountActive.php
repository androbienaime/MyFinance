<?php

namespace App\Http\Middleware;

use App\Enums\MerchantStatus;
use Closure;
use Illuminate\Http\Request;

class EnsureMerchantAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $merchant = $request->user('merchant');

        if (! $merchant || $merchant->status !== MerchantStatus::Active || ! $merchant->account->is_active) {
            $merchant?->currentAccessToken()?->delete();

            return response()->json(['message' => 'Compte marchand inactif.'], 403);
        }

        return $next($request);
    }
}