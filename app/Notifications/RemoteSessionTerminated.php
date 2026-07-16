<?php
// app/Notifications/RemoteSessionTerminated.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoteSessionTerminated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?string $terminatedByAdminName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Votre session MyFinance a été déconnectée')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Votre session active sur MyFinance vient d\'être terminée à distance.');

        if ($this->terminatedByAdminName) {
            $mail->line("Cette action a été effectuée par un administrateur ({$this->terminatedByAdminName}).");
        } else {
            $mail->line('Cette action a été déclenchée automatiquement suite à une connexion depuis un autre appareil.');
        }

        $mail->line('Si vous n\'êtes pas à l\'origine de cette situation ou si vous avez des doutes, contactez immédiatement un administrateur.')
            ->salutation('L\'équipe MyFinance');

        return $mail;
    }
}