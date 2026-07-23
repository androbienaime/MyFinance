<?php

namespace App\Filament\Resources\Core\PermissionLevelRequirements\Pages;

use App\Filament\Resources\Core\PermissionLevelRequirements\PermissionLevelRequirementResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermissionLevelRequirement extends CreateRecord
{
    protected static string $resource = PermissionLevelRequirementResource::class;
}
