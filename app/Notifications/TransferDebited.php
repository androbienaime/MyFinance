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
        return [WhatsAppChannel::class];
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
}