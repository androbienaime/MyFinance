<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $accessToken;
    protected string $phoneNumberId;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken   = config('services.whatsapp.access_token');
        $apiVersion           = config('services.whatsapp.api_version', 'v20.0');
        $this->apiUrl         = "https://graph.facebook.com/{$apiVersion}/{$this->phoneNumberId}/messages";
    }

    /**
     * Envoie un message texte libre.
     * Ne fonctionne que dans les 24h suivant le dernier message reçu du client.
     */
    public function sendText(string $to, string $message): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->formatNumber($to),
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $message,
            ],
        ];

        return $this->send($payload);
    }

    /**
     * Envoie un message basé sur un template pré-approuvé Meta.
     * Nécessaire pour toute notification "à froid" (hors fenêtre de 24h),
     * ce qui couvre la quasi-totalité des notifications automatiques (transactions, alertes...).
     *
     * @param array $parameters Paramètres positionnels du template (ex: ['1500 HTG', 'compte #A123'])
     */
    public function sendTemplate(string $to, string $templateName, array $parameters = [], string $languageCode = 'fr'): array
    {
        $components = [];

        if (!empty($parameters)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn ($param) => [
                    'type' => 'text',
                    'text' => (string) $param,
                ], $parameters),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->formatNumber($to),
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->send($payload);
    }

    protected function send(array $payload): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->post($this->apiUrl, $payload);

            if ($response->failed()) {
                Log::error('WhatsApp API error', [
                    'status'  => $response->status(),
                    'body'    => $response->json(),
                    'payload' => $payload,
                ]);

                throw new Exception('Échec de l\'envoi WhatsApp: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('WhatsApp send exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Formate le numéro au format attendu par Meta : indicatif pays + numéro, sans "+", sans espaces.
     * Adapté ici pour Haïti (+509) par défaut si aucun indicatif n'est présent.
     */
    protected function formatNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (!str_starts_with($number, '509') && strlen($number) === 8) {
            $number = '509' . $number;
        }

        return $number;
    }
}