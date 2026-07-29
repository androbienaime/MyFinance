<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('currencies')->insert($this->currencies());
    }


     public function currencies(): array
    {
        return $currencies = [
            ['iso_code' => 'HTG', 'name' => 'Haitian Gourde', 'symbol' => 'G', 'exchange_rate' => 130.0, 'country' => 'Haiti', 'is_active' => true],
            ['iso_code' => 'USD', 'name' => 'United States Dollar', 'symbol' => '$', 'exchange_rate' => 1.0, 'country' => 'United States', 'is_active' => true],
            ['iso_code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.85, 'country' => 'Eurozone', 'is_active' => true],
        ];

    }
}
