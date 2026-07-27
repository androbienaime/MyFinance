<?php

namespace App\Filament\Resources\Core\EarlyWithdrawalFees\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EarlyWithdrawalFeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type_of_account_id')
                ->label('Type de compte')
                ->relationship('typeOfAccount', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Un seul reglage de frais par type de compte.'),

                TextInput::make('fee_percentage')
                    ->label('Pourcentage de frais')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('Applique sur le solde total du compte au moment du reglement anticipe.'),

                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true)
                    ->helperText('Si desactive, le pourcentage global par defaut s\'applique a la place.'),
        ]);
        
    }
}
