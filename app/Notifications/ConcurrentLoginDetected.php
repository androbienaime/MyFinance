<?php
// app/Notifications/ConcurrentLoginDetected.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class ConcurrentLoginDetected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $ip,
        protected string $userAgent,
        protected bool $wasConcurrent,
        protected bool $isNewDevice,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Nouvelle connexion à votre compte MyFinance')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Une connexion vient d\'avoir lieu sur votre compte avec les détails suivants :')
            ->line("**Adresse IP** : {$this->ip}")
            ->line("**Appareil / navigateur** : {$this->userAgent}")
            ->line("**Date** : " . now()->format('d/m/Y à H:i:s'));

        if ($this->wasConcurrent) {
            $mail->line('⚠️ Votre session précédente active a été automatiquement déconnectée suite à cette nouvelle connexion.');
        }

        if ($this->isNewDevice) {
            $mail->line('⚠️ Cette connexion provient d\'un appareil non reconnu sur votre compte.');
        }

        $mail->line('Si vous êtes à l\'origine de cette connexion, aucune action n\'est requise.')
            ->line('**Si ce n\'est pas vous**, contactez immédiatement un administrateur et changez votre mot de passe.')
            ->salutation('L\'équipe MyFinance');

        return $mail;
    }
}