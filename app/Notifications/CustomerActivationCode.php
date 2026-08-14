<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerActivationCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $code) {}

    public function via($notifiable): array
    {
        return ['mail', WhatsAppChannel::class];
    }

    public function toWhatsApp($notifiable): array
    {
        
        \Illuminate\Support\Facades\Log::error("[DEV] Code activation pour {$notifiable->id}: {$this->code}");

        return [
            'template' => 'customer_activation_code',
            'language' => 'fr',
            'parameters' => [$this->code],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
       return (new MailMessage)
            ->line('Code d\'activation de votre compte .')
            ->action('code :', $this->code)
            ->line('Merci d\'utiliser notre application!');
    }

    
}