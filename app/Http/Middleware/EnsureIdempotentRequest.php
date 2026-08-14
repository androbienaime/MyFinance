<?php

namespace App\Http\Middleware;

use App\Models\Core\MerchantIdempotencyKey;
use Closure;
use Illuminate\Http\Request;

class EnsureIdempotentRequest
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');

        if (! $key) {
            return response()->json(['message' => 'Le header Idempotency-Key est requis pour cette operation.'], 400);
        }

        $apiKey = $request->attributes->get('merchant_api_key');
        $requestHash = hash('sha256', $request->getContent());

        $existing = MerchantIdempotencyKey::where('merchant_api_key_id', $apiKey->id)
            ->where('idempotency_key', $key)
            ->first();

        if ($existing) {
            if ($existing->request_hash !== $requestHash) {
                return response()->json([
                    'message' => 'Cette cle d\'idempotence a deja ete utilisee avec un contenu different.',
                ], 422);
            }

            // Rejoue la reponse d'origine, sans jamais re-executer l'action.
            return response()->json($existing->response_body, $existing->response_status);
        }

        $response = $next($request);

        MerchantIdempotencyKey::create([
            'merchant_api_key_id' => $apiKey->id,
            'idempotency_key' => $key,
            'request_hash' => $requestHash,
            'response_status' => $response->getStatusCode(),
            'response_body' => json_decode($response->getContent(), true),
            'expires_at' => now()->addHours(24),
        ]);

        return $response;
    }
}