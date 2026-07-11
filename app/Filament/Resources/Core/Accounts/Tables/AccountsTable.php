<?php

namespace App\Filament\Resources\Core\Accounts\Tables;

use App\Actions\AccountRestorationAction;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Actions\GuardedDeleteAction;
use App\Filament\Actions\GuardedDeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('typeOfAccount.name')
                    ->searchable(),
                TextColumn::make('customer.person.full_name')
                    ->label(__('Full name'))
                    ->searchable(),
                TextColumn::make('balance')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employee.first_name')
                    ->label(__('Employee'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
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
                Action::make('restoreAccount')
                ->label('Restaurer le compte')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn ($record) => ! $record->is_active)
                ->requiresConfirmation()
                ->modalHeading('Restaurer ce compte ?')
                ->modalDescription(fn ($record) => "Le compte {$record->code} sera reactive et son solde restaure a partir de la derniere cloture.")
                ->modalSubmitActionLabel('Confirmer la restauration')
                ->action(function ($record) {
                    try {
                        $transaction = app(AccountRestorationAction::class)->handle(
                            accountCode: $record->code,
                            employee: auth()->user()->employee,
                        );

                        Notification::make()
                            ->title($transaction->status === \App\Enums\TransactionStatus::Pending
                                ? 'Restauration en attente d\'approbation'
                                : 'Compte restaure avec succes')
                            ->success()
                            ->send();
                    } catch (TransactionRejectedException $e) {
                        Notification::make()
                            ->title('Restauration refusee')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    GuardedDeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ])->defaultSort('updated_at', 'desc');
    }
}
