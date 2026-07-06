<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;


class Role extends SpatieRole
{
    /**
     * Un role est considere "siege" s'il porte la permission
     * system.full-access. Centralise ici plutot que reecrit
     * a chaque endroit qui a besoin de le savoir.
     */
    public function isHeadOfficeRole(): bool
    {
        return $this->hasPermissionTo('system.full-access');
    }

    /**
     * Roles qu'un utilisateur donne a le droit de distribuer a
     * quelqu'un d'autre. Utilise par le formulaire Filament ET
     * par toute future API - une seule source de verite.
     */
    public function scopeAssignableBy(Builder $query, User $actor): Builder
    {
        if ($actor->isHeadOffice()) {
            return $query;
        }

        return $query->whereDoesntHave('permissions', fn ($q) => $q->where('name', 'system.full-access'));
    }
}
