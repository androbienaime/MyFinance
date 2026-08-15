<?php

namespace App\Filament\Resources\Core\MerchantProfiles\Schemas;

use App\Enums\MerchantStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantProfilesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations commerciales')
                ->columns(2)
                ->schema([
                    TextInput::make('business_name')->label('Nom commercial')->required()->columnSpanFull(),
                    TextInput::make('category')->label('Categorie'),
                    TextInput::make('business_registration_number')->label('NIF / Patente'),
                    Textarea::make('address')->label('Adresse')->columnSpanFull(),
                    TextInput::make('transaction_fee_percentage')
                        ->label('Frais par transaction (override)')
                        ->numeric()->minValue(0)->maxValue(100)->suffix('%')
                        ->helperText('Laisser vide pour utiliser le pourcentage global par defaut.'),
                ]),

            Section::make('Statut')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Statut')
                        ->options(collect(MerchantStatus::cases())->mapWithKeys(fn (MerchantStatus $c) => [$c->value => ucfirst($c->value)]))
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('rejection_reason')
                        ->label('Motif (rejet/suspension)')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn ($record) => filled($record?->rejection_reason)),
                ]),
        ]);
    }
}