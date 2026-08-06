<?php

namespace App\Filament\Resources\Core\Reports\Pages;

use App\Models\Core\Report;
use App\Filament\Resources\Core\Reports\ReportResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Report $record) => auth()->user()->can('update', $record)),
            Action::make('review')
                ->label('Marquer comme revu')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (Report $record) => auth()->user()->can('review', $record))
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reviewer_notes')->label('Notes (optionnel)')->rows(3),
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
        ];
    }
}