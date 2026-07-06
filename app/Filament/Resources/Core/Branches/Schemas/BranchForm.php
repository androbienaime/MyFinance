<?php

namespace App\Filament\Resources\Core\Branches\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('address_id')
                    ->relationship('address', 'id')
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
