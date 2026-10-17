<?php

namespace App\Filament\Resources\Core\Accounts\Tables\Actions;

use App\Models\Core\Account;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;

class AccountStatementActions
{
    public static function download(): Action
    {
        return Action::make('downloadStatement')
            ->label('Télécharger le relevé (PDF)')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->visible(fn (Account $record) => auth()->user()->can('view', $record))
            ->schema(self::dateRangeSchema())
            ->modalSubmitActionLabel('Télécharger')
            ->action(fn (Account $record, array $data) => redirect()->to(
                route('statements.account.download', self::routeParams($record, $data))
            ));
    }

    public static function print(): Action
    {
        return Action::make('printStatement')
            ->label('Aperçu / Imprimer')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->visible(fn (Account $record) => auth()->user()->can('view', $record))
            ->schema(self::dateRangeSchema())
            ->modalSubmitActionLabel('Voir l\'aperçu')
            ->action(fn (Account $record, array $data) => redirect()->to(
                route('statements.account.print', self::routeParams($record, $data))
            ));
    }

    private static function dateRangeSchema(): array
    {
        return [
            Grid::make(2)->schema([
                DatePicker::make('from')
                    ->label('Du')
                    ->default(now()->subMonth())
                    ->maxDate(now())
                    ->required(),

                DatePicker::make('to')
                    ->label('Au')
                    ->default(now())
                    ->maxDate(now())
                    ->afterOrEqual('from')
                    ->required(),
            ]),
        ];
    }

    private static function routeParams(Account $record, array $data): array
    {
        return ['account' => $record->id, 'from' => $data['from'], 'to' => $data['to']];
    }
}