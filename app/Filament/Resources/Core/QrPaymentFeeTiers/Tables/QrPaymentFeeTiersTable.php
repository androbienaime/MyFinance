<?php

namespace App\Filament\Resources\Core\QrPaymentFeeTiers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class QrPaymentFeeTiersTable
{
     public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('merchantProfile.business_name')
                    ->label('Marchand')
                    ->placeholder('Global (tous marchands)')
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'gray'),

                TextColumn::make('currency.iso_code')
                    ->label('Devise')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('min_amount')
                    ->label('De')
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('max_amount')
                    ->label('A')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('Sans plafond'),

                TextColumn::make('fee_percentage')
                    ->label('Frais')
                    ->suffix('%')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('merchant_profile_id')
            ->filters([
                SelectFilter::make('merchant_profile_id')
                    ->label('Marchand')
                    ->relationship('merchantProfile', 'business_name')
                    ->searchable(),

                SelectFilter::make('currency_id')
                    ->label('Devise')
                    ->relationship('currency', 'name'),

                TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->trueLabel('Actif')
                    ->falseLabel('Inactif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
