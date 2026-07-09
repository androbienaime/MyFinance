<?php
// app/Models/Concerns/HasDeletionGuard.php

namespace App\Models\Concerns;

use App\Exceptions\ProtectedDeletionException;

/**
 * Empêche globalement la suppression d'un modèle (delete et forceDelete)
 * tant que la méthode canBeDeleted() du modèle retourne false.
 *
 * S'applique à TOUT contexte : Filament, Tinker, API, Jobs, Console...
 * car branché directement sur le cycle de vie Eloquent.
 */
trait HasDeletionGuard
{
    public static function bootHasDeletionGuard(): void
    {
        static::deleting(function ($model) {
            if (! $model->canBeDeleted()) {
                throw ProtectedDeletionException::forModel($model);
            }
        });
    }

    /**
     * Vérifie la règle de suppression sans lever d'exception.
     * Utile pour l'affichage conditionnel (UI, Filament, API).
     */
    public function isDeletionBlocked(): bool
    {
        return ! $this->canBeDeleted();
    }
}