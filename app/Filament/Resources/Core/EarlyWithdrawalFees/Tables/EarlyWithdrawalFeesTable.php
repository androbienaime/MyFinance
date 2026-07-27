<?php

namespace App\Filament\Resources\Core\EarlyWithdrawalFees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EarlyWithdrawalFeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('typeOfAccount.name')
                    ->label('Type de compte')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fee_percentage')
                    ->label('Frais')
                    ->suffix('%')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Modifie le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('typeOfAccount.name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
