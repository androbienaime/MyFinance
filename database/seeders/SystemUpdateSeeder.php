<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\PreparesPermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemUpdateSeeder extends Seeder
{
    use PreparesPermissions;

    protected array $permissions = [
        'system_updates.run' => 95, // reserve au sommet de la hierarchie, action systeme critique
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->preparePermissions($this->permissions);
    }
}
