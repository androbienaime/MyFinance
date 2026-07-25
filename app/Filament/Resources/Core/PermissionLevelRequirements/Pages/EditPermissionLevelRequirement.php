<?php

namespace App\Filament\Resources\Core\PermissionLevelRequirements\Pages;

use App\Filament\Resources\Core\PermissionLevelRequirements\PermissionLevelRequirementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPermissionLevelRequirement extends EditRecord
{
    protected static string $resource = PermissionLevelRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
