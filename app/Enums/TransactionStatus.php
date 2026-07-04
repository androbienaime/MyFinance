<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Approuve',
            self::Completed => 'Complete',
            self::Rejected => 'Rejete',
        };
    }

    /**
     * Transitions autorisees depuis cet etat. Utilise pour rejeter
     * toute transition qui ne figure pas dans cette carte — meme si
     * quelqu'un essaie de modifier le statut directement en base ou
     * via une requete mal formee, la logique metier (dans les Actions
     * qu'on ecrira plus tard) s'appuiera sur cette regle pour refuser.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Approved, self::Rejected, self::Completed],
            self::Approved => [self::Completed, self::Rejected],
            self::Completed, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}