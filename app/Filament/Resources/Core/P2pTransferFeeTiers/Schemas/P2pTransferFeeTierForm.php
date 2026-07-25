<?php

namespace App\Filament\Resources\Core\P2pTransferFeeTiers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class P2pTransferFeeTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('min_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('max_amount')
                    ->numeric()
                    ->default(null),
                TextInput::make('fee_amount')
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
