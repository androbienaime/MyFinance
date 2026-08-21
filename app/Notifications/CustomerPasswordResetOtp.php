<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerPasswordResetOtp extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $code) {}

    public function via($notifiable): array
    {
        return [WhatsAppChannel::class];
    }

    public function toWhatsApp($notifiable): array
    {
        return [
            'template' => 'customer_password_reset_otp',
            'language' => 'fr',
            'parameters' => [$this->code],
        ];
    }


    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('Code de réinitialisation de votre mot de passe.')
            ->line('Votre code : ' . $this->code)
            ->action('Réinitialiser mon mot de passe', url('/customer/reset-password'))
            ->line('Merci d\'utiliser notre application!');
    }
}