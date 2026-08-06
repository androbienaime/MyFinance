<?php

namespace Database\Seeders\Concerns;

use Spatie\Permission\Models\Permission;
use App\Models\Core\Role;
use App\Models\Core\PermissionLevelRequirement;

trait PreparesPermissions
{
    /**
     * Cree/met a jour les permissions + leur min_level_to_assign,
     * puis resynchronise super_admin avec la totalite des permissions.
     *
     * @param array<string,int> $permissions name => min_level_to_assign
     */
    protected function preparePermissions(array $permissions): void
    {
        foreach ($permissions as $name => $minLevel) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );

            PermissionLevelRequirement::updateOrCreate(
                ['permission_id' => $permission->id],
                ['min_level_to_assign' => $minLevel]
            );

            $this->command?->info("Permission prete : {$name} (min_level_to_assign = {$minLevel})");
        }
    }
}