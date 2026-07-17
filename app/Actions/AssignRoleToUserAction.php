<?php

namespace App\Actions;

use App\Models\Core\Role;
use App\Models\Core\RoleAssignmentLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AssignRoleToUserAction
{
    public function handle(User $targetUser, ?Role $role, ?User $assignedBy = null): void
    {
        if ($assignedBy) {
            $this->guardNewRole($assignedBy, $role);
            $this->guardCurrentRole($assignedBy, $targetUser);
        }

        $targetUser->syncRoles($role ? [$role] : []);

        RoleAssignmentLog::create([
            'user_id' => $targetUser->id,
            'role_id' => $role?->id,
            'assigned_by' => $assignedBy?->id,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Vérifie que l'acteur a le droit d'attribuer CE rôle (s'il y en a un).
     * Un retrait de rôle (role = null) n'a pas besoin de cette vérification.
     */
    protected function guardNewRole(User $assignedBy, ?Role $role): void
    {
        if (!$role) {
            return;
        }

        if (!$this->canAssign($assignedBy, $role)) {
            throw new AuthorizationException(
                "Vous n'avez pas le niveau requis pour assigner le rôle « {$role->name} »."
            );
        }
    }

    /**
     * Empêche de modifier/retirer le rôle ACTUEL de la cible si ce rôle est
     * d'un niveau que l'acteur ne pourrait pas lui-même attribuer. Sans ce
     * garde-fou, un admin de niveau intermédiaire pourrait rétrograder ou
     * dégrader un utilisateur ayant un rôle de niveau supérieur au sien,
     * contournant la hiérarchie dans l'autre sens.
     */
    protected function guardCurrentRole(User $assignedBy, User $targetUser): void
    {
        if ($assignedBy->isSuperAdmin()) {
            return;
        }

        $currentRole = $targetUser->roles()->first();

        if (!$currentRole) {
            return;
        }

        $assignerMaxLevel = $assignedBy->roles()->max('level') ?? 0;

        if ($currentRole->level >= $assignerMaxLevel) {
            throw new AuthorizationException(
                "Vous ne pouvez pas modifier le rôle actuel de cet utilisateur « {$currentRole->name} » (niveau {$currentRole->level})."
            );
        }
    }

    protected function canAssign(User $assignedBy, Role $role): bool
    {
        if ($assignedBy->isSuperAdmin()) {
            return true;
        }

        // Permission de base requise dans tous les cas
        if (!$assignedBy->can('roles.assign')) {
            return false;
        }

        // Un utilisateur ne peut assigner que des rôles de niveau
        // STRICTEMENT inférieur au plus haut niveau qu'il possède lui-même
        $assignerMaxLevel = $assignedBy->roles()->max('level') ?? 0;

        return $role->level < $assignerMaxLevel;
    }
}