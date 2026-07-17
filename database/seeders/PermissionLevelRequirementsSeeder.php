<?php

// database/seeders/PermissionLevelRequirementsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Core\PermissionLevelRequirement;

class PermissionLevelRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            'system.full-access' => 100,
            'permissions.assign_any' => 100,

            'customers.view' => 0,
            'customers.create' => 10,
            'customers.update' => 10,

            'accounts.view' => 0,
            'accounts.create' => 10,
            'accounts.update' => 20,
            'accounts.toggle-active' => 30,

            'transactions.view' => 0,
            'transactions.deposit' => 10,
            'transactions.withdraw' => 10,
            'transactions.payment' => 10,
            'transactions.approve' => 40,
            'transactions.settlement' => 50,

            'employees.view' => 0,
            'employees.create' => 30,
            'employees.update' => 30,

            'branches.manage' => 60,

            'approval-thresholds.manage' => 70,

            'roles.manage' => 90,
            'roles.assign' => 90,
            'roles.view_any' => 30,
            'roles.view' => 30,

            'reports.view' => 20,

            'users.view' => 20,
            'users.create' => 90, // dormante, bloquée par UserPolicy::create()
            'users.update' => 40,
            'users.toggle-active' => 50,
            'users.deactivate' => 50,
            'users.reactivate' => 50,
            'users.force_logout' => 60,

            'types_of_accounts.view' => 0,
            'types_of_accounts.create' => 30,
            'types_of_accounts.update' => 30,

            'account_closures.view' => 20,
            'account_closures.create' => 40,
            'account_closures.update' => 40,

            'people.view' => 0,
            'people.update' => 20,
            'people.create' => 20,

            'employee_branch_histories.view' => 20,
            'employee_branch_histories.update' => 40,
            'employee_branch_histories.create' => 40,

            'login_attempts.view' => 40,
            'trusted_devices.view' => 30,
            'trusted_devices.trust' => 50,
            'trusted_devices.revoke' => 60,
        ];

        foreach ($requirements as $permissionName => $minLevel) {
            $permission = Permission::where('name', $permissionName)->first();

            if (!$permission) {
                $this->command?->warn("Permission introuvable, ignorée : {$permissionName}");
                continue;
            }

            PermissionLevelRequirement::updateOrCreate(
                ['permission_id' => $permission->id],
                ['min_level_to_assign' => $minLevel]
            );
        }
    }
}