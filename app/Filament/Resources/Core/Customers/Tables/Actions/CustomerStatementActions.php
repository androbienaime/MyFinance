<?php

namespace App\Filament\Resources\Core\Customers\Tables\Actions;

use App\Models\Core\Customer;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;

class CustomerStatementActions
{
    public static function download(): Action
    {
        return Action::make('downloadCustomerStatement')
            ->label('Télécharger le relevé (PDF)')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('from')->label('Du')->default(now()->subMonth())->required(),
                    DatePicker::make('to')->label('Au')->default(now())->required(),
                ]),
            ])
            ->visible(fn (Customer $record) => auth()->user()->can('view', $record))
            ->modalSubmitActionLabel('Télécharger')
            ->action(fn (Customer $record, array $data) => redirect()->to(
                route('statements.customer.download', ['customer' => $record->id, 'from' => $data['from'], 'to' => $data['to']])
            ));
    }

    public static function print(): Action
    {
        return Action::make('printCustomerStatement')
            ->label('Aperçu / Imprimer')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('from')->label('Du')->default(now()->subMonth())->required(),
                    DatePicker::make('to')->label('Au')->default(now())->required(),
                ]),
            ])
            ->visible(fn (Customer $record) => auth()->user()->can('view', $record))
            ->modalSubmitActionLabel('Voir l\'aperçu')
            ->action(fn (Customer $record, array $data) => redirect()->away(
                route('statements.customer.print', ['customer' => $record->id, 'from' => $data['from'], 'to' => $data['to']])
            ));
    }
}