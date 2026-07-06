<?php

namespace App\Filament\Resources\Core\Employees\Pages;

use App\Actions\AssignRoleToUserAction;
use App\Filament\Resources\Core\Employees\EmployeeResource;
use App\Models\Core\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

   protected function mutateFormDataBeforeSave(array $data): array
    {
        // Traite le role separement (via l'Action), puis retire ces cles
        // "virtuelles" avant que Filament ne les passe a Employee::update().
        if (array_key_exists('role_id', $data)) {
            $this->syncRoleSafely($data['role_id'] ?? null);
        }

        unset($data['user_email'], $data['user_password'], $data['role_id']);

        return $data;
    }

    private function syncRoleSafely(?int $roleId): void
    {
        $user = $this->record->user;

        if (! $user) {
            return;
        }

        $role = $roleId ? Role::find($roleId) : null;

        // Meme point de passage qu'a la creation : aucune logique
        // d'autorisation dupliquee, tout passe par l'Action.
        app(AssignRoleToUserAction::class)->handle($user, $role, Auth::user());
    }

    protected function afterSave(): void
    {
        if ($this->shouldSendEmailChangeNotification ?? false) {
            $this->record->user->notify(new \App\Notifications\EmailChanged());
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
