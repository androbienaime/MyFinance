<?php

namespace App\Notifications\Channels;

use App\Services\WhatsAppService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppChannel
{
    public function __construct(protected WhatsAppService $whatsapp)
    {
    }

    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        // Le notifiable doit exposer une méthode routeNotificationForWhatsApp()
        // ou un attribut "phone_number" / "telephone"
        $to = $notifiable->routeNotificationFor('whatsapp')
            ?? $notifiable->phone_number
            ?? null;

        if (empty($to)) {
            Log::warning('WhatsAppChannel: aucun numéro de téléphone trouvé pour le notifiable', [
                'notifiable_id'    => $notifiable->id ?? null,
                'notification'     => get_class($notification),
            ]);
            return;
        }

        $data = $notification->toWhatsApp($notifiable);

        try {
            if (isset($data['template'])) {
                $this->whatsapp->sendTemplate(
                    $to,
                    $data['template'],
                    $data['parameters'] ?? [],
                    $data['language'] ?? 'fr'
                );
            } else {
                $this->whatsapp->sendText($to, $data['text'] ?? (string) $data);
            }
        } catch (Throwable $e) {
            Log::error('Échec envoi WhatsApp pour notification ' . get_class($notification), [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
            // On ne relance pas l'exception pour ne pas casser le flux principal
            // (ex: une transaction ne doit pas échouer si WhatsApp est down)
        }
    }
}