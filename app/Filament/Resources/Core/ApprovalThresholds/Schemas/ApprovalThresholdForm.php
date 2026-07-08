<?php

namespace App\Filament\Resources\Core\ApprovalThresholds\Schemas;

use App\Enums\TransactionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ApprovalThresholdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(TransactionType::class)
                    ->required(),
                TextInput::make('min_amount')
                    ->minValue(0)
                    ->required()
                    ->numeric(),
                TextInput::make('required_levels')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)   
                    ->default(1),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
