<?php

namespace App\Filament\Resources\Core\Accounts\Tables;

use App\Actions\AccountRestorationAction;
use App\Exceptions\TransactionRejectedException;
use App\Filament\Actions\GuardedDeleteAction;
use App\Filament\Actions\GuardedDeleteBulkAction;
use App\Filament\Resources\Core\Accounts\Tables\Actions\AccountStatementActions;
use App\Models\Core\Account;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('typeOfAccount.name')
                    ->label(__('myfinance.type_of_account'))
                    ->searchable(),
                TextColumn::make('holder_type')
                    ->label(__('myfinance.holder_type'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('customer.person.full_name')
                    ->label(__('myfinance.full_name'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('customer.person', function (Builder $q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
                        })
                        ->orWhereHas('customer', function (Builder $q) use ($search) {
                            $q->where('phone_number', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('balance')
                    ->money(fn(Account $record) => $record?->currency?->iso_code)
                    ->sortable(),
                TextColumn::make('currency.iso_code')
                    ->label(__('myfinance.currency'))
                    ->searchable(),
                TextColumn::make('employee.firstname')
                    ->tooltip(fn (Account $record) => $record->employee?->fullname)
                    ->label(__('myfinance.employee'))
                    ->visible(auth()->user()->isSuperAdmin()),
                    // ->searchable(),
                TextColumn::make('customer.phone_number')
                    ->label(__('myfinance.phone_number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
              EditAction::make()
                ->mutateFormDataUsing(function (array $data, $record) {
                    // Bloque toute modification si le compte est désactivé,
                    // sauf si cette modification consiste justement à le réactiver
                    if (
                        $record->is_active === false
                        // && array_key_exists('is_active', $data)
                        // && (bool) $data['is_active'] === false
                    ) {
                        Notification::make()
                            ->title('Modification refusée')
                            ->body('Impossible de modifier ce compte : il est désactivé.')
                            ->danger()
                            ->persistent()
                            ->send();

                        throw new Halt();
                    }

                    $currentBalance = (float) $record->balance;

                    if ($currentBalance <= 0) {
                        return $data;
                    }

                    $protectedFields = [
                        'customer_id' => 'Client',
                        'type_of_account_id' => 'Type de compte',
                        'holder_type' => 'Type de titulaire',
                        'currency_id' => 'Devise',
                    ];

                    $normalize = function ($value) {
                        if ($value instanceof BackedEnum) {
                            return (string) $value->value;
                        }

                        return $value === null ? null : (string) $value;
                    };

                    $changedFields = collect($protectedFields)
                        ->filter(function ($label, $field) use ($data, $record, $normalize) {
                            if (! array_key_exists($field, $data)) {
                                return false;
                            }

                            return $normalize($data[$field]) !== $normalize($record->{$field});
                        });

                    if ($changedFields->isNotEmpty()) {
                        $fieldNames = $changedFields->values()->implode(', ');

                        Notification::make()
                            ->title('Modification refusée')
                            ->body("Impossible de modifier ({$fieldNames}) : ce compte a un solde positif ({$currentBalance}).")
                            ->danger()
                            ->persistent()
                            ->send();

                        throw new Halt();
                    }

                    return $data;
                }),

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
                }),                
                AccountStatementActions::print(),
                AccountStatementActions::download(),

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
