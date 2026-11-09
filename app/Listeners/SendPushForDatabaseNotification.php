<?php

namespace App\Listeners;

use App\Models\Core\Customer;
use App\Services\ExpoPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Chaque notification enregistrée pour un client (dépôt, retrait, paiement QR, transfert…)
 * est aussi envoyée en push, avec le même titre et le même message que dans l'application.
 * Aucune notification existante n'a besoin d'être modifiée.
 */
class SendPushForDatabaseNotification implements ShouldQueue
{
    /** Envoyé seulement après validation de la transaction SQL (paiement, dépôt…) et hors de la requête. */
    public bool $afterCommit = true;

    public function __construct(private ExpoPushService $push) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database' || ! $event->notifiable instanceof Customer) {
            return; // les notifications des marchands ne vont pas dans cette application
        }

        $stored = $event->response instanceof DatabaseNotification ? $event->response : null;
        $data = $stored?->data ?? (method_exists($event->notification, 'toArray') ? $event->notification->toArray($event->notifiable) : []);

        $probe = strtolower(($data['transaction_type'] ?? '').' '.($data['type'] ?? '').' '.class_basename($event->notification));
        $default = match (true) {
            str_contains($probe, 'withdraw') || str_contains($probe, 'retrait') => 'Retrait confirmé',
            str_contains($probe, 'transfer') => 'Transfert confirmé',
            str_contains($probe, 'deposit') || str_contains($probe, 'depot') => 'Dépôt confirmé',
            str_contains($probe, 'qr') || str_contains($probe, 'payment') => 'Paiement effectué',
            default => 'LT Finance',
        };

        $title = (string) ($data['title'] ?? $data['subject'] ?? $default);
        $body = (string) ($data['message'] ?? $data['body'] ?? '');

        $this->push->sendToCustomer($event->notifiable, $title, $body, ['url' => '/notifications']);
    }
}
