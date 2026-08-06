<?php

namespace App\Filament\Resources\Core\Reports\Tables;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Core\Report;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ReportType $state) => $state->label())
                    ->color(fn (ReportType $state) => $state->color()),
                TextColumn::make('category')
                    ->label('Categorie')
                    ->badge()
                    ->formatStateUsing(fn (ReportCategory $state) => $state->label()),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('branch.name')
                    ->label('Succursale')
                    ->placeholder('Toutes')
                    ->searchable(),
                TextColumn::make('employee.full_name')
                    ->label('Employe')
                    ->placeholder('Systeme')
                    ->searchable(),
                TextColumn::make('period_start')
                    ->label('Periode')
                    ->date()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ReportStatus $state) => $state->label())
                    ->color(fn (ReportStatus $state) => $state->color()),
                TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        ReportType::Automatic->value => ReportType::Automatic->label(),
                        ReportType::Manual->value => ReportType::Manual->label(),
                    ]),
                SelectFilter::make('category')
                    ->label('Categorie')
                    ->options(collect(ReportCategory::cases())
                        ->mapWithKeys(fn (ReportCategory $case) => [$case->value => $case->label()])
                        ->toArray()),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        ReportStatus::Pending->value => ReportStatus::Pending->label(),
                        ReportStatus::Reviewed->value => ReportStatus::Reviewed->label(),
                        ReportStatus::Archived->value => ReportStatus::Archived->label(),
                    ]),
                SelectFilter::make('branch_id')
                    ->label('Succursale')
                    ->relationship('branch', 'name')
                    ->searchable(),
                SelectFilter::make('employee_id')
                    ->label('Employe')
                    ->relationship('employee', 'full_name')
                    ->searchable(),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label('Du'),
                        DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('period_start', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('period_end', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Report $record) => auth()->user()->can('update', $record)),
                Action::make('review')
                    ->label('Marquer comme revu')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Report $record) => auth()->user()->can('review', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reviewer_notes')
                            ->label('Notes (optionnel)')
                            ->rows(3),
                    ])
                    ->action(function (Report $record, array $data) {
                        $record->markReviewed(auth()->user(), $data['reviewer_notes'] ?? null);

                        Notification::make()->title('Rapport marque comme revu')->success()->send();
                    }),
                Action::make('archive')
                    ->label('Archiver')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn (Report $record) => auth()->user()->can('archive', $record))
                    ->requiresConfirmation()
                    ->action(function (Report $record) {
                        $record->archive();

                        Notification::make()->title('Rapport archive')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}