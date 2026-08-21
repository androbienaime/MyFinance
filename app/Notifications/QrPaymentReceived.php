<?php

namespace App\Notifications;

use App\Models\Core\Customer;
use App\Models\Core\QrPaymentRequest;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QrPaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected QrPaymentRequest $payment, protected Customer $customer) {}

    public function via($notifiable): array
    {
        return ['database', 'mail', WhatsAppChannel::class];
    }

    public function toWhatsApp($notifiable): array
    {
        return [
            'template' => 'qr_payment_received',
            'language' => 'fr',
            'parameters' => [number_format($this->payment->amount, 2)],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
       return (new MailMessage)
            ->line('Paiement QR recu.')
            ->line('Montant : ' . number_format($this->payment->amount, 2))
            ->line('Reference : ' . $this->payment->reference)
            ->line('Merci d\'utiliser notre application!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'qr_payment_received',
            'amount' => (float) $this->payment->amount,
            'reference' => $this->payment->reference,
            'message' => "Paiement recu : {$this->payment->amount} HTG.",
        ];
    }
}