<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasReportsScope
{
    protected function reportsScopeLevel(): string
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->can('reports.view.all')) {
            return 'all';
        }

        if ($user->can('reports.view.branch')) {
            return 'branch';
        }

        return 'own';
    }

    /**
     * Applique le scope de visibilite sur une requete, via la relation
     * "employee" DIRECTE du modele interroge (chaque modele — Account,
     * Transaction, Customer — a son propre createur/gestionnaire, qui
     * n'est pas forcement le meme d'un modele a l'autre). $relation
     * permet de nommer cette relation si elle differe du nom par defaut
     * "employee" (ex: relation renommee, ou intermediaire necessaire).
     */
    protected function applyReportsScope(Builder $query, string $relation = 'employee'): Builder
    {
        $user = auth()->user();

        return match ($this->reportsScopeLevel()) {
            'all' => $query,

            'branch' => $query->whereHas(
                $relation,
                fn ($q) => $q->where('branch_id', $user->currentBranchId())
            ),

            'own' => $query->whereHas(
                $relation,
                fn ($q) => $q->where('id', $user->employee->id)
            ),
        };
    }
}