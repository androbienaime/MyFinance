<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MerchantApiCredentials extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $accountCode, protected string $temporaryPassword) {}

    public function via($notifiable): array
    {
        $channels = ['database'];
        if(!empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        if(!empty($notifiable->phone_number)) {
            $channels[] = WhatsAppChannel::class;
        }
        return $channels;
    }

    public function toWhatsApp($notifiable): array
    {
        return [
            'template' => 'merchant_api_credentials',
            'language' => 'fr',
            'parameters' => [$this->accountCode, $this->temporaryPassword],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('Vos identifiants de connexion au tableau de bord marchand.')
            ->line('Votre code : ' . $this->accountCode)
            ->line('Votre mot de passe temporaire : ' . $this->temporaryPassword)
            ->line('Merci d\'utiliser notre application!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'merchant_activated',
            'message' => 'Votre compte marchand est actif. Identifiants du tableau de bord envoyes.',
        ];
    }
}