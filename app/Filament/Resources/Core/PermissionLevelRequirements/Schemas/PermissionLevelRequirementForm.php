<?php

namespace App\Filament\Resources\Core\PermissionLevelRequirements\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PermissionLevelRequirementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('permission_id')
                    ->relationship('permission', 'name')
                    ->required(),
                TextInput::make('min_level_to_assign')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
