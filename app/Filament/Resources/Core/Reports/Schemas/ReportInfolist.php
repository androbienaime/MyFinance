<?php

namespace App\Filament\Resources\Core\Reports\Schemas;

use App\Enums\ReportType;
use App\Models\Core\Report;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('type')
                                ->label('Type')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state->label())
                                ->color(fn ($state) => $state->color()),
                            TextEntry::make('category')
                                ->label('Categorie')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state->label()),
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state->label())
                                ->color(fn ($state) => $state->color()),
                            TextEntry::make('branch.name')
                                ->label('Succursale')
                                ->placeholder('Toutes succursales'),
                        ]),

                    TextEntry::make('title')
                        ->label('Titre')
                        ->columnSpanFull(),

                    Grid::make(3)
                        ->schema([
                            TextEntry::make('employee.full_name')
                                ->label('Redige par')
                                ->placeholder('Systeme (automatique)'),
                            TextEntry::make('period_start')
                                ->label('Debut de periode')
                                ->date()
                                ->placeholder('—'),
                            TextEntry::make('period_end')
                                ->label('Fin de periode')
                                ->date()
                                ->placeholder('—'),
                        ]),
                ]),

            Section::make('Contenu')
                ->schema([
                    TextEntry::make('content')
                        ->hiddenLabel()
                        ->prose()
                        ->columnSpanFull(),
                ])
                ->visible(fn (Report $record) => filled($record->content)),

            Section::make('Donnees chiffrees')
                ->description('Instantane genere automatiquement par le systeme.')
                ->schema([
                    KeyValueEntry::make('data')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ])
                ->visible(fn (Report $record) => $record->type === ReportType::Automatic && filled($record->data)),

            Section::make('Revue administrative')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('reviewer.name')
                                ->label('Revu par')
                                ->placeholder('Pas encore revu'),
                            TextEntry::make('reviewed_at')
                                ->label('Revu le')
                                ->dateTime()
                                ->placeholder('—'),
                        ]),
                    TextEntry::make('reviewer_notes')
                        ->label('Notes du reviseur')
                        ->columnSpanFull()
                        ->placeholder('Aucune note'),
                ]),
        ]);
    }
}