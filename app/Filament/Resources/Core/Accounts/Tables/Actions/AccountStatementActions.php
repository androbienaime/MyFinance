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
            ->visible(fn () => auth()->user()->can('view', \App\Models\Core\Account::class))
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('from')->label('Du')->default(now()->subMonth())->required(),
                    DatePicker::make('to')->label('Au')->default(now())->required(),
                ]),
            ])
            ->modalSubmitActionLabel('Télécharger')
            ->action(fn (Account $record, array $data) => redirect()->to(
                route('statements.account.download', ['account' => $record->id, 'from' => $data['from'], 'to' => $data['to']])
            ));
    }

    public static function print(): Action
    {
        return Action::make('printStatement')
            ->label('Aperçu / Imprimer')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->visible(fn () => auth()->user()->can('view', \App\Models\Core\Account::class))
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('from')->label('Du')->default(now()->subMonth())->required(),
                    DatePicker::make('to')->label('Au')->default(now())->required(),
                ]),
            ])
            ->modalSubmitActionLabel('Voir l\'aperçu')
            ->action(function (Account $record, array $data) {
                $url = route('statements.account.print', ['account' => $record->id, 'from' => $data['from'], 'to' => $data['to']]);

                return redirect()->away($url);
            });
    }
}