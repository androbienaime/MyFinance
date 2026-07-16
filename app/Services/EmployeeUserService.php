<?php

// app/Services/EmployeeUserService.php
namespace App\Services;

use App\Models\User;
use App\Models\Core\Employee;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EmployeeUserService
{
    public function createUserForEmployee(Employee $employee, array $overrides = []): User
    {
        app()->instance('creating_user_via_employee', true);

        try {
            return DB::transaction(function () use ($employee, $overrides) {
                $user = User::create(array_merge([
                    'name' => $employee->full_name,
                    'email' => $employee->email,
                    'password' => bcrypt(Str::random(24)), // temporaire, à réinitialiser au premier login
                    'is_active' => true,
                ], $overrides));

                $employee->update(['user_id' => $user->id]);

                return $user;
            });
        } finally {
            app()->forgetInstance('creating_user_via_employee');
        }
    }
}