<?php

namespace App\Filament\Resources\Core\RoleAssignmentLogs\Pages;

use App\Filament\Resources\Core\RoleAssignmentLogs\RoleAssignmentLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoleAssignmentLogs extends ListRecords
{
    protected static string $resource = RoleAssignmentLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
