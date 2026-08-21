<?php

namespace App\Notifications;

use App\Models\Core\MerchantProfile;
use App\Models\Core\QrPaymentRequest;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QrPaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected QrPaymentRequest $payment, protected MerchantProfile $merchant) {}

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
            'template' => 'qr_payment_confirmed',
            'language' => 'fr',
            'parameters' => [
                number_format($this->payment->total_amount, 2),
                $this->merchant->business_name,
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
       return (new MailMessage)
            ->line('Paiement QR confirme.')
            ->line('Montant : ' . number_format($this->payment->total_amount, 2))
            ->line('Marchand : ' . $this->merchant->business_name)
            ->line('Merci d\'utiliser notre application!');
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