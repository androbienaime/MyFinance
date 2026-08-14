<?php

namespace App\Filament\Resources\Core\QrPaymentTransactions\Tables;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QrPaymentTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (TransactionType $state) => $state->label())
                    ->color(fn (TransactionType $state) => $state === TransactionType::QrPaymentFee ? 'gray' : 'success'),

                TextColumn::make('direction')
                    ->label('Sens')
                    ->badge()
                    ->formatStateUsing(fn (?TransactionDirection $state) => match ($state) {
                        TransactionDirection::Debit => 'Sortant',
                        TransactionDirection::Credit => 'Entrant',
                        null => '—',
                    })
                    ->color(fn (?TransactionDirection $state) => match ($state) {
                        TransactionDirection::Debit => 'danger',
                        TransactionDirection::Credit => 'success',
                        null => 'gray',
                    }),

                TextColumn::make('account.code')
                    ->label('Compte')
                    ->searchable()
                    ->description(fn ($record) => $record->account?->customer?->person?->full_name
                        ?? $record->account?->merchantProfile?->business_name),

                TextColumn::make('counterpartyAccount.code')
                    ->label('Contrepartie')
                    ->placeholder('—'),

                TextColumn::make('initiatedByCustomer.person.full_name')
                    ->label('Client')
                    ->placeholder('—'),

                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . ' ' . ($record->account?->currency?->iso_code ?? ''))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        TransactionType::QrPayment->value => 'Paiement QR',
                        TransactionType::QrPaymentFee->value => 'Frais de paiement QR',
                    ]),

                Filter::make('created_at')
                    ->schema([
                        Grid::make(2)->schema([
                            // DateTimePicker::make('from')->label('Du'),
                            // DateTimePicker::make('to')->label('Au'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->emptyStateHeading('Aucune transaction QR complétée')
            ->emptyStateDescription('Les paiements QR finalisés apparaîtront ici.');
    }
}