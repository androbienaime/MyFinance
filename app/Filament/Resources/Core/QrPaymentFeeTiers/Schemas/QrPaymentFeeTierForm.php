<?php

namespace App\Filament\Resources\Core\QrPaymentFeeTiers\Schemas;

use App\Models\Core\Currency;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QrPaymentFeeTierForm
{
     public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('merchant_profile_id')
                ->label('Marchand')
                ->relationship('merchantProfile', 'business_name')
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Laisser vide pour créer un palier GLOBAL (applicable à tout marchand sans palier propre actif dans cette devise).'),

            Select::make('currency_id')
                ->label('Devise')
                ->relationship('currency', 'name')
                ->default(fn () => Currency::where('iso_code', setting('financial.default_currency', default: 'HTG'))->first()?->id)
                ->required()
                ->helperText('Le palier ne s\'applique qu\'aux paiements dans CETTE devise. Créez un palier distinct pour chaque devise que vous voulez couvrir.'),

            TextInput::make('min_amount')
                ->label('Montant minimum')
                ->numeric()
                ->required()
                ->minValue(0),

            TextInput::make('max_amount')
                ->label('Montant maximum')
                ->numeric()
                ->minValue(0)
                ->helperText('Laisser vide pour "sans plafond" (dernier palier).'),

            TextInput::make('fee_percentage')
                ->label('Pourcentage de frais')
                ->numeric()
                ->required()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%'),

            Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }
}
