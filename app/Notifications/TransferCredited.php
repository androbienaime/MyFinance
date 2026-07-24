<?php

namespace App\Notifications;

use App\Models\Core\Account;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TransferCredited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Account $from,
        protected Account $to,
        protected float $amount,
    ) {}

    public function via($notifiable): array
    {
        return [WhatsAppChannel::class];
    }

    public function toWhatsApp($notifiable): array
    {
        return [
            'template' => 'transfer_credited',
            'language' => 'fr',
            'parameters' => [
                number_format($this->amount, 2),
                $this->from->code,
                number_format($this->to->balance, 2),
            ],
        ];
    }
}