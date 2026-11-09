<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Changement de mot de passe du client CONNECTÉ.
 * (« Mot de passe oublié » : déjà géré par AuthController::forgotPassword / resetPassword.)
 */
class PasswordController extends Controller
{
    /** Erreur 422 (et non 401) si le mot de passe actuel est faux : un 401 déconnecterait l'application. */
    public function change(Request $request): JsonResponse
    {
        $customer = $request->user('customer');

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:10', 'confirmed', 'different:current_password'],
        ]);

        if (! Hash::check($data['current_password'], $customer->password)) {
            throw ValidationException::withMessages(['current_password' => ['Le mot de passe actuel est incorrect.']]);
        }

        $customer->forceFill(['password' => Hash::make($data['password'])])->save();

        // Les autres appareils sont déconnectés ; l'appareil courant reste connecté.
        $current = $customer->currentAccessToken();
        $customer->tokens()->when($current, fn ($q) => $q->where('id', '!=', $current->id))->delete();

        return response()->json(['message' => 'Mot de passe modifié.']);
    }
}
