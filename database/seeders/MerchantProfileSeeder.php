<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\PreparesPermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MerchantProfileSeeder extends Seeder
{
    use PreparesPermissions;

    protected array $permissions = [
        'merchant_profiles.view_any' => 40,
        'merchant_profiles.view' => 40,
        'merchant_profiles.create' => 20, // creation liee a accounts.create, niveau bas
        'merchant_profiles.update' => 40,
        'merchant_profiles.approve' => 60,
        'merchant_profiles.suspend' => 60,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->preparePermissions($this->permissions);
    }
}
