<?php

namespace App\Filament\Resources\Core\CaisseSessions\Tables;

use App\Actions\CloseCaisseSessionAction;
use App\Enums\CaisseSessionStatus;
use App\Exceptions\CaisseSessionException;
use App\Filament\Resources\Core\CaisseSessions\Pages\ViewCaisseSession;
use App\Models\Core\CaisseSession;
use App\Models\Core\Currency;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CaisseSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('status')
                    ->label('')
                    ->icon(fn (CaisseSessionStatus $state) => match ($state) {
                        CaisseSessionStatus::Open => 'heroicon-o-lock-open',
                        CaisseSessionStatus::Closed => 'heroicon-o-lock-closed',
                    })
                    ->color(fn (CaisseSessionStatus $state) => $state->color())
                    ->tooltip(fn (CaisseSessionStatus $state) => $state->label()),

                TextColumn::make('employee.full_name')
                    ->label('Caissier')
                    ->description(fn (CaisseSession $record) => $record->branch?->name)
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('session_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                // Resume multi-devise compact : un badge par devise, colore
                // selon l'ecart de cette devise precise.
                TextColumn::make('balances')
                    ->label('Devises')
                    ->badge()
                    ->state(fn (CaisseSession $record) => $record->balances->map(
                        fn ($b) => $b->currency->iso_code.': '.number_format((float) ($b->closing_balance_declared ?? $b->opening_balance_declared), 2)
                    )->all())
                    ->color(fn ($state, CaisseSession $record) => $record->balances
                        ->first(fn ($b) => str_starts_with($state, $b->currency->iso_code))
                        ?->hasDiscrepancy() ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (CaisseSessionStatus $state) => $state->label())
                    ->color(fn (CaisseSessionStatus $state) => $state->color()),

                TextColumn::make('opened_at')->label('Ouverture')->time('H:i')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('closed_at')->label('Fermeture')->time('H:i')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Succursale')->relationship('branch', 'name')->searchable(),
                SelectFilter::make('employee_id')->label('Caissier')->relationship('employee', 'full_name')->searchable(),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        CaisseSessionStatus::Open->value => CaisseSessionStatus::Open->label(),
                        CaisseSessionStatus::Closed->value => CaisseSessionStatus::Closed->label(),
                    ]),
                SelectFilter::make('currency')
                    ->label('Devise')
                    ->options(fn () => Currency::pluck('iso_code', 'id'))
                    ->query(fn (Builder $q, array $data) => $q->when(
                        $data['value'] ?? null,
                        fn ($q, $currencyId) => $q->whereHas('balances', fn ($bq) => $bq->where('currency_id', $currencyId))
                    )),
                Filter::make('with_discrepancy')
                    ->label('Avec ecart')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereHas(
                        'balances',
                        fn ($q) => $q->whereNotNull('closing_discrepancy')->where('closing_discrepancy', '!=', 0)
                    )),
                Filter::make('session_date')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('session_date', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('session_date', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('close')
                    ->label('Fermer ma caisse')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (CaisseSession $record) => auth()->user()->can('close', $record))
                    ->requiresConfirmation()
                    ->schema(fn (CaisseSession $record) => [
                        Grid::make(1)->schema(
                            $record->balances->map(fn ($balance) => \Filament\Schemas\Components\Section::make($balance->currency->iso_code)
                                ->schema([
                                    TextInput::make("closing.{$balance->currency_id}.declared")
                                        ->label('Montant compte en caisse')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->required(),
                                    Textarea::make("closing.{$balance->currency_id}.comment")
                                        ->label('Commentaire (obligatoire en cas d\'ecart)')
                                        ->rows(2),
                                ]))->all()
                        ),
                    ])
                    ->action(function (CaisseSession $record, array $data) {
                        try {
                            app(CloseCaisseSessionAction::class)->handle($record, $data['closing'] ?? []);

                            Notification::make()->title('Caisse fermee')->success()->send();
                        } catch (CaisseSessionException $e) {
                            Notification::make()
                                ->title('Impossible de fermer la caisse')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('forceClose')
                    ->label('Fermeture forcee')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->visible(fn (CaisseSession $record) => auth()->user()->can('forceClose', $record))
                    ->requiresConfirmation()
                    ->modalDescription('A utiliser uniquement si le caissier ne peut pas fermer lui-meme sa caisse.')
                    ->schema(fn (CaisseSession $record) => [
                        Grid::make(1)->schema(
                            $record->balances->map(fn ($balance) => \Filament\Schemas\Components\Section::make($balance->currency->iso_code)
                                ->schema([
                                    TextInput::make("closing.{$balance->currency_id}.declared")
                                        ->label('Montant compte (verifie par vous)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->required(),
                                    Textarea::make("closing.{$balance->currency_id}.comment")
                                        ->label('Raison de la fermeture forcee')
                                        ->required()
                                        ->rows(2),
                                ]))->all()
                        ),
                    ])
                    ->action(function (CaisseSession $record, array $data) {
                        try {
                            app(CloseCaisseSessionAction::class)->handle($record, $data['closing'] ?? []);

                            Notification::make()->title('Caisse fermee de force')->warning()->send();
                        } catch (CaisseSessionException $e) {
                            Notification::make()
                                ->title('Impossible de fermer la caisse')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('session_date', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->striped();
    }
}