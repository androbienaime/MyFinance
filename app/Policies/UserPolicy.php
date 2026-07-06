<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Pas de before() generique ici : gerer les comptes utilisateurs
     * (creation, desactivation, permissions directes) est une action
     * sensible qui merite sa propre permission explicite, pas un bypass
     * automatique via une autre capacite.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.update');
    }

    /**
     * Personne ne peut se desactiver soi-meme, meme le siege - evite
     * de se retrouver bloque hors du panel par erreur (ou par malice
     * d'un compte compromis qui voudrait bloquer l'acces a tout le monde
     * en se desactivant en dernier).
     */
    public function toggleActive(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->can('users.toggle-active');
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }
}