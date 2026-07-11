<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class Permission2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'account_closures.view', 'account_closures.update', 'account_closures.create',
            'people.view', 'people.update', 'people.create',
            'employee_branch_histories.view', 'employee_branch_histories.update', 'employee_branch_histories.create',
            'transactions.settlement'

        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }
    }
}
