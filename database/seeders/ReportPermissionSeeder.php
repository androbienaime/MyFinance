<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\PreparesPermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportPermissionSeeder extends Seeder
{
    use PreparesPermissions;

    /**
     * name => min_level_to_assign
     */
    protected array $permissions = [
        // Rapports
        'reports.view' => 10,
        'reports.view_any' => 20,
        'reports.create' => 30,
        'reports.update' => 40,
        'reports.delete' => 50,
        'reports.manage' => 60,
        'reports.view.branch' => 40,
        'reports.view.all' => 60,

        // Caisses
        'caisse_sessions.open' => 10,
        'caisse_sessions.close' => 10,
        'caisse_sessions.view' => 10,
        'caisse_sessions.view_any' => 20,
        'caisse_sessions.update' => 30,
        'caisse_sessions.delete' => 40,
        'caisse_sessions.manage' => 50,

        // Daily Activity Analytics
        'daily_activity_analytics.view' => 10,
        'daily_activity_analytics.view_any' => 20,
        'caisse_session_balances.view_any' => 20,
        'caisse_session_balances.view' => 20,
        'caisse_session_balances.update' => 20,
        'caisse_session_balances.delete' => 80,
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->preparePermissions($this->permissions);
    }
}
