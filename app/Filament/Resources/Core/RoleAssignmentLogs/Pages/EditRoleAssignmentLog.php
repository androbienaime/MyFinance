<?php

namespace App\Filament\Resources\Core\RoleAssignmentLogs\Pages;

use App\Filament\Resources\Core\RoleAssignmentLogs\RoleAssignmentLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoleAssignmentLog extends EditRecord
{
    protected static string $resource = RoleAssignmentLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
