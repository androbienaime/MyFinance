<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\PreparesPermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencyPermissionSeeder extends Seeder
{
    use PreparesPermissions;

    protected array $permissions = [
        'currencies.view_any' => 95,
        'currencies.view' => 95,
        'currencies.update' => 96,
        'currencies.create' => 97,
        'currencies.delete' => 100,

        'currency_rates.view_any' => 95,
        'currency_rates.view' => 95
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->preparePermissions($this->permissions);
    }
}
