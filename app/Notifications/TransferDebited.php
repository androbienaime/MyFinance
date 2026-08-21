<?php

namespace App\Notifications;

use App\Models\Core\Account;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TransferDebited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Account $from,
        protected Account $to,
        protected float $amount,
        protected float $feeAmount = 0.0,
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
            'template' => 'transfer_debited',
            'language' => 'fr',
            'parameters' => [
                number_format($this->amount, 2),
                $this->to->code,
                number_format($this->feeAmount, 2),
                number_format($this->from->balance, 2),
            ],
        ];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
       return (new \Illuminate\Notifications\Messages\MailMessage)
            ->line('Transfert debite.')
            ->line('Montant : ' . number_format($this->amount, 2))
            ->line('Vers : ' . $this->to->code)
            ->line('Frais : ' . number_format($this->feeAmount, 2))
            ->line('Solde : ' . number_format($this->from->balance, 2))
            ->line('Merci d\'utiliser notre application!');
    }
}