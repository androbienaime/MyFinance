<?php 
// database/seeders/RoleLevelSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Core\Role;

class RoleLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            'super_admin' => 100,
            'admin_branche' => 50,
            'superviseur' => 30,
            'caissier' => 10,
            'consultation' => 5,
        ];

        foreach ($levels as $name => $level) {
            Role::where('name', $name)->update(['level' => $level]);
        }
    }
}