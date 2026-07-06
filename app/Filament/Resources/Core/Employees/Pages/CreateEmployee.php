<?php

namespace App\Filament\Resources\Core\Employees\Pages;

use App\Actions\AssignRoleToUserAction;
use App\Filament\Resources\Core\Employees\EmployeeResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Core\Role;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    
    protected function handleRecordCreation(array $data): Model
    {
        $user = User::create([
            'name' => trim("{$data['firstname']} {$data['lastname']}"),
            'email' => $data['user_email'],
            'password' => Hash::make($data['user_password']),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        if (! empty($data['role_id'])) {
            $role = Role::find($data['role_id']);

            if ($role) {
                // Meme regle qu'utiliserait une future API : rien de
                // duplique, rien d'oubliable.
                app(AssignRoleToUserAction::class)->handle($user, $role, Auth::user());
            }
        }

        return static::getModel()::create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'identity_number' => $data['identity_number'] ?? null,
            'branch_id' => $data['branch_id'],
            'user_id' => $user->id,
        ]);
    }


}
