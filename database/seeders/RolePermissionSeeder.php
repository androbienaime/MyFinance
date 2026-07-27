<?php

namespace Database\Seeders;

use App\Support\Updates\HasTrackedSeeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    use HasTrackedSeeders;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->callTracked(PermissionSeeder::class);
        $this->callTracked(Permission2Seeder::class);
        $this->callTracked(SecurityPermissionsSeeder::class);
        $this->callTracked(RoleLevelSeeder::class);
        $this->callTracked(PermissionLevelRequirementsSeeder::class);
        $this->callTracked(TransferAndAuditPermissionsSeeder::class);
    }
}
