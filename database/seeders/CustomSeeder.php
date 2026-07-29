<?php

namespace Database\Seeders;

use App\Support\Updates\HasTrackedSeeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomSeeder extends Seeder
{
    use HasTrackedSeeders;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->callTracked(CurrencySeeder::class);
    }
}
