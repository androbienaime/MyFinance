<?php

namespace App\Filament\Resources\Core\Customers\Tables;

use App\Filament\Actions\GuardedDeleteAction;
use App\Filament\Actions\GuardedDeleteBulkAction;
use App\Filament\Resources\Core\Customers\Tables\Actions\CustomerStatementActions;
use App\Filament\Resources\Core\Customers\Tables\Actions\EnableCustomerOnlineAccessAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('code')
                //     ->searchable(),
                TextColumn::make('person.full_name')
                    ->label(__('myfinance.full_name'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('person', function (Builder $q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                            ->orWhere('phone_number', 'like', "%{$search}%");;
                        });
                    }),
                TextColumn::make('person.gender')
                    ->label(__('myfinance.gender'))
                    ->searchable(),
                TextColumn::make('person.identityDocuments.document_number')
                    ->label(__('myfinance.document_number'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(),
                TextColumn::make('person.addresses.phone')
                    ->label(__('myfinance.phone_number'))
                    ->searchable(),
                TextColumn::make('accounts_count')
                    ->badge()
                    ->counts('accounts')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('myfinance.number_of_accounts')),
                IconColumn::make('is_online')
                ->label(__('myfinance.online_access'))
                ->boolean(),
                TextColumn::make('employee.fullname')
                    ->label(__('myfinance.employee'))
                    // ->searchable()
                    ->visible(auth()->user()->isSuperAdmin()),
                TextColumn::make('person.addresses.city.name')
                    ->label(__('myfinance.city'))
                    ->toggleable(isToggledHiddenByDefault: true)
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
                EnableCustomerOnlineAccessAction::make(),
                GuardedDeleteAction::make(),
                CustomerStatementActions::download(),
                CustomerStatementActions::print(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    GuardedDeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])->defaultSort('updated_at', 'desc');
    }
}
