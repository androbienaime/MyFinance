<?php

namespace App\Filament\Resources\Core\PermissionLevelRequirements\Pages;

use App\Filament\Resources\Core\PermissionLevelRequirements\PermissionLevelRequirementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermissionLevelRequirements extends ListRecords
{
    protected static string $resource = PermissionLevelRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
