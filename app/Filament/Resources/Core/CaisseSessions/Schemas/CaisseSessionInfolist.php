<?php

namespace App\Filament\Resources\Core\CaisseSessions\Schemas;

use App\Models\Core\CaisseSession;
use App\Models\Core\CaisseSessionBalance;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CaisseSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('employee.full_name')->label('Caissier'),
                            TextEntry::make('branch.name')->label('Succursale'),
                            TextEntry::make('session_date')->label('Date')->date(),
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state->label())
                                ->color(fn ($state) => $state->color()),
                        ]),
                ]),

            // Une sous-section par devise, avec le detail complet
            RepeatableEntry::make('balances')
                ->hiddenLabel()
                ->schema([
                    Section::make(fn (CaisseSessionBalance $record) => 'Devise : '.$record->currency->iso_code)
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('opening_balance_expected')->label('Attendu (ouverture)')->money('USD'),
                                    TextEntry::make('opening_balance_declared')->label('Declare (ouverture)')->money('USD'),
                                    TextEntry::make('opening_discrepancy')
                                        ->label('Ecart ouverture')
                                        ->money('USD')
                                        ->color(fn ($state) => (float) $state === 0.0 ? 'success' : 'danger'),
                                ]),
                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('total_deposits')->label('Depots')->money('USD')->color('success'),
                                    TextEntry::make('total_withdrawals')->label('Retraits')->money('USD')->color('danger'),
                                    TextEntry::make('total_other_movements')->label('Autres')->money('USD'),
                                ]),
                            Grid::make(3)
                                ->schema([
                                    TextEntry::make('closing_balance_expected')->label('Attendu (fermeture)')->money('USD')->placeholder('—'),
                                    TextEntry::make('closing_balance_declared')->label('Declare (fermeture)')->money('USD')->placeholder('—'),
                                    TextEntry::make('closing_discrepancy')
                                        ->label('Ecart fermeture')
                                        ->money('USD')
                                        ->weight('bold')
                                        ->placeholder('—')
                                        ->color(fn ($state) => $state === null ? 'gray' : ((float) $state === 0.0 ? 'success' : 'danger')),
                                ]),
                            TextEntry::make('closing_comment')
                                ->label('Commentaire')
                                ->columnSpanFull()
                                ->placeholder('Aucun commentaire'),
                        ]),
                ]),
        ]);
    }
}