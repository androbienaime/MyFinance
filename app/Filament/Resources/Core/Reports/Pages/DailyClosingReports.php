<?php

namespace App\Filament\Resources\Core\Reports\Pages;

use App\Enums\ReportCategory;
use App\Filament\Resources\Core\Reports\ReportResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyClosingReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    public function getTitle(): string
    {
        return 'Rapports de cloture journaliere';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('all')
                ->label('Tous les rapports')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn () => ReportResource::getUrl('index')),
            Action::make('monthlySummary')
                ->label('Resumes mensuels')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->url(fn () => ReportResource::getUrl('monthly-summary')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ReportResource::getEloquentQuery()
                    ->where('category', ReportCategory::DailyClosing->value)
            )
            ->columns([
                TextColumn::make('branch.name')->label('Succursale'),
                TextColumn::make('period_start')->label('Date')->date()->sortable(),
                TextColumn::make('data.cashiers_count')->label('Caissiers'),
                TextColumn::make('data.cashiers_not_closed')
                    ->label('Non fermees')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('data.total_deposits')->label('Depots')->money('USD'),
                TextColumn::make('data.total_withdrawals')->label('Retraits')->money('USD'),
                TextColumn::make('data.net_cash_flow')->label('Flux net')->money('USD'),
                TextColumn::make('data.sessions_with_discrepancy')
                    ->label('Ecarts')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('data.total_discrepancy')->label('Montant ecarts')->money('USD'),
            ])
            ->filters([
                SelectFilter::make('branch_id')->label('Succursale')->relationship('branch', 'name'),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('period_start', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('period_start', '<=', $d))),
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