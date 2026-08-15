<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\AuthenticateMerchantAction;
use App\Exceptions\TransactionRejectedException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(Request $request, AuthenticateMerchantAction $action)
    {
        $data = $request->validate([
            'account_code' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $throttleKey = 'merchant-login:' . $data['account_code'] . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => "Trop de tentatives. Reessayez dans {$seconds} secondes.",
            ], 429);
        }

        try {
            $result = $action->handle($data['account_code'], $data['password'], $data['device_name'] ?? null);

            RateLimiter::clear($throttleKey);

            return response()->json([
                'token' => $result['token'],
                'expires_at' => $result['expires_at'],
                'merchant' => [
                    'business_name' => $result['merchant']->business_name,
                    'account_code' => $result['merchant']->account->code,
                ],
            ]);
        } catch (TransactionRejectedException $e) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function logout(Request $request)
    {
        $request->user('merchant')->currentAccessToken()->delete();

        return response()->json(['message' => 'Deconnecte.']);
    }

    public function logoutAllDevices(Request $request)
    {
        $request->user('merchant')->tokens()->delete();

        return response()->json(['message' => 'Deconnecte de toutes les sessions du tableau de bord.']);
    }
}