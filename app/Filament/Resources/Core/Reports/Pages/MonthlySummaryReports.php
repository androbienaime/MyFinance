<?php

namespace App\Filament\Resources\Core\Reports\Pages;

use App\Enums\ReportCategory;
use App\Filament\Resources\Core\Reports\ReportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MonthlySummaryReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    public function getTitle(): string
    {
        return 'Resumes mensuels';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('all')
                ->label('Tous les rapports')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn () => ReportResource::getUrl('index')),
            Action::make('dailyClosing')
                ->label('Rapports journaliers')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->url(fn () => ReportResource::getUrl('daily-closing')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ReportResource::getEloquentQuery()
                    ->where('category', ReportCategory::MonthlySummary->value)
            )
            ->columns([
                TextColumn::make('branch.name')->label('Succursale')->placeholder('Toutes succursales (siege)'),
                TextColumn::make('period_start')->label('Mois')->date('F Y')->sortable(),
                TextColumn::make('data.sessions_closed')->label('Sessions fermees'),
                TextColumn::make('data.total_deposits')->label('Depots')->money('USD'),
                TextColumn::make('data.total_withdrawals')->label('Retraits')->money('USD'),
                TextColumn::make('data.net_cash_flow')->label('Flux net')->money('USD'),
                TextColumn::make('data.sessions_with_discrepancy')
                    ->label('Ecarts')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('data.new_customers')->label('Nouveaux clients'),
                TextColumn::make('data.new_accounts')->label('Nouveaux comptes'),
            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Succursale')->relationship('branch', 'name'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => ReportResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('period_start', 'desc');
    }
}