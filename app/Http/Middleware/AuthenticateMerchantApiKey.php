<?php

namespace App\Http\Middleware;

use App\Enums\MerchantStatus;
use App\Models\Core\MerchantApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateMerchantApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('X-API-Key') ?? $request->bearerToken();

        if (! $header || ! str_contains($header, '.')) {
            return response()->json(['message' => 'Cle API manquante ou invalide.'], 401);
        }

        [$prefix, $secret] = explode('.', $header, 2);

        $apiKey = MerchantApiKey::where('key_prefix', $prefix)
            ->where('is_active', true)
            ->with('merchantProfile.account')
            ->first();

        if (! $apiKey || ! Hash::check($secret, $apiKey->key_hash)) {
            return response()->json(['message' => 'Cle API invalide.'], 401);
        }

        $merchant = $apiKey->merchantProfile;

        if ($merchant->status !== MerchantStatus::Active || ! $merchant->account->is_active) {
            return response()->json(['message' => 'Compte marchand inactif.'], 403);
        }

        $apiKey->update([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
        ]);

        $request->attributes->set('merchant', $merchant);
        $request->attributes->set('merchant_api_key', $apiKey);

        return $next($request);
    }
}