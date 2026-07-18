<?php

namespace App\Notifications;

use App\Models\Core\Transaction;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TransactionConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Transaction $transaction)
    {
    }

    public function via($notifiable): array
    {
        return [WhatsAppChannel::class];
        // Tu peux combiner: return [WhatsAppChannel::class, 'mail', 'database'];
    }

    /**
     * Version "texte libre" — utilisable seulement si le client a écrit
     * dans les dernières 24h (rare pour une notif automatique).
     */
    public function toWhatsApp($notifiable): array
    {
        $montant = number_format($this->transaction->amount, 2) . ' HTG';
        $type    = $this->transaction->type?->label() ?? 'Transaction';
        $compte  = $this->transaction->account->code ?? '';
        $full_name = $this->transaction->account->customer->person->full_name ?? ' ';
        $transaction_id = $this->transaction->code ?? '';
        $solde   = number_format($this->transaction->account->balance, 2) . ' HTG';
        $date = $this->transaction->created_at;

        // --- Option A: template pré-approuvé Meta (recommandé, fonctionne toujours) ---
        return [
            'template'   => 'transaction_confirmed', // nom du template créé dans Meta Business Manager
            'language'   => 'fr',
            'parameters' => [$full_name, $type, $montant, $compte, $solde,$transaction_id, $date],
        ];

        // --- Option B: texte libre (décommente si tu préfères, mais limité aux 24h) ---
        // return [
        //     'text' => "Bonjour, votre {$type} de {$montant} sur le compte {$compte} a été confirmée. "
        //             . "Nouveau solde : {$solde}.",
        // ];
    }
}