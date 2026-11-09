<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Core\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    /** Enregistre (ou réattribue) le jeton Expo de ce téléphone au client connecté. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255', 'regex:/^Expo(nent)?PushToken\[[A-Za-z0-9_-]+\]$/'],
            'platform' => ['nullable', 'string', 'in:ios,android'],
        ]);

        // Si ce téléphone était lié à un autre client (téléphone partagé), le jeton passe au client connecté.
        PushToken::updateOrCreate(
            ['token' => $data['token']],
            ['customer_id' => $request->user('customer')->id, 'platform' => $data['platform'] ?? null, 'last_used_at' => now()],
        );

        return response()->json(['message' => 'Notifications activées.']);
    }

    /** Retire le jeton (déconnexion) : ce téléphone ne reçoit plus les alertes de ce client. */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string']]);

        PushToken::where('token', $data['token'])
            ->where('customer_id', $request->user('customer')->id)
            ->delete();

        return response()->json(['message' => 'Notifications désactivées sur cet appareil.']);
    }
}
