<?php

namespace App\Notifications;

use App\Models\Core\MerchantProfile;
use App\Models\Core\QrPaymentRequest;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class QrPaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected QrPaymentRequest $payment, protected MerchantProfile $merchant) {}

    public function via($notifiable): array
    {
        return ['database', WhatsAppChannel::class];
    }

    public function toWhatsApp($notifiable): array
    {
        return [
            'template' => 'qr_payment_confirmed',
            'language' => 'fr',
            'parameters' => [
                number_format($this->payment->total_amount, 2),
                $this->merchant->business_name,
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'qr_payment_confirmed',
            'amount' => (float) $this->payment->total_amount,
            'merchant_name' => $this->merchant->business_name,
            'message' => "Paiement de {$this->payment->total_amount} HTG confirme chez {$this->merchant->business_name}.",
        ];
    }
}