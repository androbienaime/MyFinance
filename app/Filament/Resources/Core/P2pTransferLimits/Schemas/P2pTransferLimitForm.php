<?php

namespace App\Filament\Resources\Core\P2pTransferLimits\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class P2pTransferLimitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('max_per_transaction')
                    ->required()
                    ->numeric(),
                TextInput::make('max_daily_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('max_daily_count')
                    ->required()
                    ->numeric(),
                TextInput::make('max_monthly_amount')
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
