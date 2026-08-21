<?php

// app/Notifications/P2pTransferOtpCode.php
namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class P2pTransferOtpCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $code,
        protected float $amount,
        protected float $fee,
        protected string $toAccountCode,
    ) {}

    public function via($notifiable): array
    {
        $channels = [];
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
            'template' => 'p2p_transfer_otp',
            'language' => 'fr',
            'parameters' => [
                $this->code,
                number_format($this->amount, 2),
                number_format($this->fee, 2),
                $this->toAccountCode,
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
       return (new MailMessage)
            ->line('Code de transfert P2P.')
            ->line('code : ' . $this->code)
            ->line('Merci d\'utiliser notre application!');
    }
}