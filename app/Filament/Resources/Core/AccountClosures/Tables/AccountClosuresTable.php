<?php

namespace App\Filament\Resources\Core\AccountClosures\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountClosuresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.customer.person.full_name')
                    ->label("Full Name")
                    ->searchable(),
                TextColumn::make('account.code')
                    ->label("Code")
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('reason')
                    ->searchable(),
                TextColumn::make('balance_at_closure')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('closed_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]) ->defaultSort('updated_at', 'desc');
    }
}
