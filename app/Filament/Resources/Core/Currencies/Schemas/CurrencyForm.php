<?php

namespace App\Filament\Resources\Core\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('symbol')
                    ->required()
                    ->maxLength(255),
                TextInput::make('iso_code')
                    ->maxLength(255),
                TextInput::make('exchange_rate')
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->onColor("success")
                    ->offColor("danger")
                    ->default(true)
                    ->required(),
            ]);
    }
}
