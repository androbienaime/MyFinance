<?php

namespace App\Http\Controllers\Api\Customer;

use App\Actions\ActivateCustomerAccountAction;
use App\Actions\AuthenticateCustomerAction;
use App\Actions\ConfirmCustomerPasswordResetAction;
use App\Actions\RequestCustomerPasswordResetAction;
use App\Exceptions\TransactionRejectedException;
use App\Http\Controllers\Controller;
use App\Models\Core\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function activate(Request $request, ActivateCustomerAccountAction $action)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'activation_code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:10'],
        ]);

        $customer = Customer::where('email', $data['identifier'])
            ->orWhere('phone_number', $data['identifier'])
            ->firstOrFail();

        try {
            $action->handle($customer, $data['activation_code'], $data['password']);

            return response()->json(['message' => 'Compte active avec succes. Vous pouvez maintenant vous connecter.']);
        } catch (TransactionRejectedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function login(Request $request, AuthenticateCustomerAction $action)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $throttleKey = 'customer-login:' . mb_strtolower($data['identifier']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => "Trop de tentatives. Reessayez dans {$seconds} secondes.",
            ], 429);
        }

        try {
            $result = $action->handle($data['identifier'], $data['password'], $data['device_name'] ?? null);

            RateLimiter::clear($throttleKey);

            return response()->json([
                'token' => $result['token'],
                'expires_at' => $result['expires_at'],
                'customer' => [
                    'id' => $result['customer']->id,
                    'name' => $result['customer']->full_name ?? $result['customer']->name,
                ],
            ]);
        } catch (TransactionRejectedException $e) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function logout(Request $request)
    {
        $request->user('customer')->currentAccessToken()->delete();

        return response()->json(['message' => 'Deconnecte.']);
    }

    public function logoutAllDevices(Request $request)
    {
        $request->user('customer')->tokens()->delete();

        return response()->json(['message' => 'Deconnecte de tous les appareils.']);
    }

    public function forgotPassword(Request $request, RequestCustomerPasswordResetAction $action)
    {
        $data = $request->validate(['identifier' => ['required', 'string']]);

        $action->handle($data['identifier']);

        // Reponse identique que le compte existe ou non - evite
        // l'enumeration de comptes clients.
        return response()->json(['message' => 'Si ce compte existe, un code a ete envoye.']);
    }

    public function resetPassword(Request $request, ConfirmCustomerPasswordResetAction $action)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:10'],
        ]);

        try {
            $action->handle($data['identifier'], $data['code'], $data['password']);

            return response()->json(['message' => 'Mot de passe reinitialise avec succes.']);
        } catch (TransactionRejectedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}