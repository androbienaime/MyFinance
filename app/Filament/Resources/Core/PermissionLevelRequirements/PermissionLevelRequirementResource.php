<?php

namespace App\Filament\Resources\Core\PermissionLevelRequirements;

use App\Filament\Resources\Core\PermissionLevelRequirements\Pages\CreatePermissionLevelRequirement;
use App\Filament\Resources\Core\PermissionLevelRequirements\Pages\EditPermissionLevelRequirement;
use App\Filament\Resources\Core\PermissionLevelRequirements\Pages\ListPermissionLevelRequirements;
use App\Filament\Resources\Core\PermissionLevelRequirements\Schemas\PermissionLevelRequirementForm;
use App\Filament\Resources\Core\PermissionLevelRequirements\Tables\PermissionLevelRequirementsTable;
use App\Models\Core\PermissionLevelRequirement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PermissionLevelRequirementResource extends Resource
{
    protected static ?string $model = PermissionLevelRequirement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Sécurité';

    public static function form(Schema $schema): Schema
    {
        return PermissionLevelRequirementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionLevelRequirementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissionLevelRequirements::route('/'),
            'create' => CreatePermissionLevelRequirement::route('/create'),
            'edit' => EditPermissionLevelRequirement::route('/{record}/edit'),
        ];
    }
}
