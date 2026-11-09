<?php

namespace App\Services;

use App\Models\Core\Customer;
use App\Models\Core\PushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Envoie une notification push (barre d'état du téléphone) à tous les téléphones d'un client, via le service Expo. */
class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * @return array<int, array> tickets renvoyés par Expo (un par téléphone) ; tableau vide si aucun téléphone enregistré.
     *                           Utile pour diagnostiquer dans tinker ; ignoré par l'écouteur.
     */
    public function sendToCustomer(Customer $customer, string $title, string $body, array $data = []): array
    {
        $tickets = [];
        $tokens = $customer->pushTokens()->pluck('token')->all();
        if (! $tokens) {
            return [];
        }

        try {
            foreach (array_chunk($tokens, 100) as $chunk) {
                $messages = array_map(fn (string $to) => [
                    'to' => $to,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'sound' => 'default',
                    'channelId' => 'default',
                    'priority' => 'high',
                ], $chunk);

                $request = Http::acceptJson()->timeout(10);
                if ($key = config('services.expo.access_token')) {
                    $request = $request->withToken($key);
                }

                $response = $request->post(self::ENDPOINT, $messages);

                if ($response->failed()) {
                    Log::warning('Expo push: échec HTTP', ['status' => $response->status(), 'body' => $response->body()]);
                    $tickets[] = ['status' => 'error', 'message' => 'HTTP '.$response->status()];
                    continue;
                }

                // Les tickets reviennent dans l'ordre des messages : on retire les téléphones désinstallés.
                foreach ($response->json('data', []) as $i => $ticket) {
                    $tickets[] = $ticket;
                    if (($ticket['status'] ?? null) === 'error' && ($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                        PushToken::where('token', $chunk[$i])->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            // Une panne du push ne doit jamais bloquer le reste.
            Log::warning('Expo push: exception', ['message' => $e->getMessage()]);
            $tickets[] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $tickets;
    }
}