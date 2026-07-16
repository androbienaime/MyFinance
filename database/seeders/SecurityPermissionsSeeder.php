<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SecurityPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Utilisateurs
            'users.view',
            'users.deactivate',
            'users.reactivate',
            'users.force_logout',

            // Audit des connexions
            'login_attempts.view',

            // Appareils de confiance
            'trusted_devices.view',
            'trusted_devices.trust',
            'trusted_devices.revoke',

            // Rôles
            'roles.assign',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}