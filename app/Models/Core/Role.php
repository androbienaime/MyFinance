<?php

// app/Models/Core/Role.php
namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Models\Permission;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'level'];

    protected $casts = ['level' => 'integer'];

    public function isHeadOfficeRole(): bool
    {
        return $this->hasPermissionTo('system.full-access');
    }

    protected static function booted(): void
    {
        static::saving(function (Role $role) {
            if (!Auth::check() || app()->runningInConsole()) {
                return;
            }

            $userMaxLevel = Auth::user()->roles()->max('level') ?? 0;

            if ($role->level >= $userMaxLevel && !Auth::user()->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'level' => "Vous ne pouvez pas définir un niveau ({$role->level}) supérieur ou égal au vôtre ({$userMaxLevel}).",
                ]);
            }
        });

        static::deleting(function (Role $role) {
            if ($role->isProtected($role)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => "Le rôle « {$role->name} » ne peut pas être supprimé (rôle protégé).",
                ]);
            }
        });
    }

    /**
     * Point d'entrée UNIQUE pour synchroniser les permissions d'un rôle.
     * La règle repose exclusivement sur permission_level_requirements :
     * l'utilisateur doit avoir un niveau >= au minimum requis pour CHAQUE
     * permission qu'il tente d'accorder — peu importe s'il la possède
     * lui-même ou non.
     */
    public function syncPermissionsSecurely(array $permissionIds): void
    {
        if (Auth::check() && !app()->runningInConsole() && !Auth::user()->isSuperAdmin()) {
            $userMaxLevel = Auth::user()->roles()->max('level') ?? 0;

            $levelRequirements = PermissionLevelRequirement::whereIn('permission_id', $permissionIds)
                ->pluck('min_level_to_assign', 'permission_id');

            $forbidden = collect($permissionIds)->filter(
                fn ($id) => ($levelRequirements->get($id, 0)) > $userMaxLevel
            );

            if ($forbidden->isNotEmpty()) {
                $names = Permission::whereIn('id', $forbidden)->pluck('name')->implode(', ');

                throw ValidationException::withMessages([
                    'permissions' => "Vous n'avez pas le niveau requis pour accorder : {$names}",
                ]);
            }
        }

        $this->permissions()->sync($permissionIds);
    }

    /**
     * Visible : rôles strictement en dessous de son niveau,
     * PLUS son propre rôle (lecture seule côté Policy).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $userMaxLevel = $user->roles()->max('level') ?? 0;
        $ownRoleIds = $user->roles()->pluck('id')->all();

        return $query->where(function (Builder $q) use ($userMaxLevel, $ownRoleIds) {
            $q->where('level', '<', $userMaxLevel);

            if (!empty($ownRoleIds)) {
                $q->orWhereIn('id', $ownRoleIds);
            }
        });
    }

    /**
     * Le rôle super_admin est intouchable, peu importe qui fait la demande.
     */
    public function isProtected(Role $role): bool
    {
        return $role->level === 100 || $role->name === 'super_admin';
    }
}