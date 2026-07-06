<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $permissions = [
            // Acces total : quiconque a cette permission est traite comme
            // "siege central" partout dans l'app (voir User::isHeadOffice()).
            'system.full-access',

            'customers.view', 'customers.create', 'customers.update',
            'accounts.view', 'accounts.create', 'accounts.update', 'accounts.toggle-active',
            'transactions.view', 'transactions.deposit', 'transactions.withdraw',
            'transactions.payment', 'transactions.approve',
            'employees.view', 'employees.create', 'employees.update',
            'branches.manage',
            'approval-thresholds.manage',
            'roles.manage',
            'reports.view',
            'users.view', 'users.create', 'users.update', 'users.toggle-active',
            'types_of_accounts.view', 'types_of_accounts.create', 'types_of_accounts.update',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }
    }
}
