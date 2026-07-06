<?php

namespace App\Actions;

use App\Models\Core\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AssignRoleToUserAction
{
    /**
     * Point de passage UNIQUE pour attribuer un role a un utilisateur.
     * Que l'appelant soit une Page Filament, un controleur API, une
     * commande artisan ou un script de seed, cette regle s'applique
     * toujours : impossible de distribuer un role siege sans en
     * etre soi-meme un.
     */
    public function handle(User $target, Role $role, User $actor): void
    {
        if (! $actor->can('roles.manage')) {
            throw new AuthorizationException('Vous n\'avez pas le droit d\'attribuer de role.');
        }

        if ($role->isHeadOfficeRole() && ! $actor->isHeadOffice()) {
            throw new AuthorizationException('Vous ne pouvez pas attribuer un role donnant acces au siege.');
        }

        $target->syncRoles($role);
    }
}