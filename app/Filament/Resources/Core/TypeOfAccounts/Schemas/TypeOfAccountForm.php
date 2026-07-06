<?php

namespace App\Filament\Resources\Core\TypeOfAccounts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TypeOfAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                TextInput::make('duration')
                    ->numeric()
                    ->default(null),
                Toggle::make('active_case_payments')
                    ->required(),
                TextInput::make('prefix')
                    ->required(),
            ]);
    }
}
