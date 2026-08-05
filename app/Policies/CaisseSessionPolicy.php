<?php

namespace App\Policies;

use App\Enums\CaisseSessionStatus;
use App\Models\Core\CaisseSession;
use App\Models\User;

class CaisseSessionPolicy
{
    /**
     * Le siege voit et gere toutes les caisses, toutes succursales
     * confondues. Sinon, chaque regle ci-dessous s'applique normalement.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'delete') {
            return false;
        }

        return $user->isHeadOffice() ? true : null;
    }

    /**
     * Tout caissier peut voir la liste (restreinte a ses propres sessions
     * par la requete du Resource) ; un superviseur/admin voit toute la
     * succursale via caisse.view.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('caisse_sessions.open') || $user->can('caisse_sessions.view');
    }

    public function view(User $user, CaisseSession $session): bool
    {
        if ($user->can('caisse_sessions.view')) {
            return true;
        }

        return $session->employee_id === $user->employee?->id;
    }

    /**
     * Ouvrir une caisse est une action que le caissier fait pour
     * lui-meme uniquement.
     */
    public function open(User $user): bool
    {
        return $user->can('caisse_sessions.open') && $user->employee !== null;
    }

    /**
     * Fermer sa propre caisse, tant qu'elle est encore ouverte.
     */
    public function close(User $user, CaisseSession $session): bool
    {
        if ($session->status !== CaisseSessionStatus::Open) {
            return false;
        }

        return $user->can('caisse_sessions.close') && $session->employee_id === $user->employee?->id;
    }

    /**
     * Fermeture forcee par un superviseur/admin — reservee aux caisses
     * restees ouvertes anormalement (caissier absent, oubli...).
     */
    public function forceClose(User $user, CaisseSession $session): bool
    {
        if ($session->status !== CaisseSessionStatus::Open) {
            return false;
        }

        return $user->can('caisse_sessions.manage');
    }

    /**
     * Une session de caisse est une piece comptable : jamais supprimable,
     * meme par le siege, une fois creee.
     */
    public function delete(User $user, CaisseSession $session): bool
    {
        return false;
    }
}