<?php

namespace App\Filament\Resources\Core\Customers\Tables;

use App\Filament\Actions\GuardedDeleteAction;
use App\Filament\Actions\GuardedDeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('person.full_name')
                    ->label(__('Full name'))
                    ->searchable(),
                TextColumn::make('person.gender')
                    ->label(__('gender'))
                    ->searchable(),
                TextColumn::make('person.identityDocuments.document_number')
                    ->label(__('identity number'))
                    ->searchable(),
                TextColumn::make('person.addresses.phone')
                    ->label(__('Phone Number'))
                    ->searchable(),
                TextColumn::make('accounts_count')
                    ->badge()
                    ->counts('accounts')
                    ->label(__('Number of accounts')),
                
                TextColumn::make('employee.first_name')
                    ->label(__('Employee'))
                    ->searchable(),
                TextColumn::make('person.addresses.city.name')
                    ->label(__('City'))
                    ->searchable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                GuardedDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    GuardedDeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
