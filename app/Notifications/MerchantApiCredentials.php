<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MerchantApiCredentials extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $accountCode, protected string $temporaryPassword) {}

    public function via($notifiable): array
    {
        return ['database', WhatsAppChannel::class];
    }

    public function toWhatsApp($notifiable): array
    {
        return [
            'template' => 'merchant_api_credentials',
            'language' => 'fr',
            'parameters' => [$this->accountCode, $this->temporaryPassword],
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'merchant_activated',
            'message' => 'Votre compte marchand est actif. Identifiants du tableau de bord envoyes.',
        ];
    }
}