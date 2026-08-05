<?php

namespace App\Filament\Resources\Core\Reports\Pages;

use App\Filament\Resources\Core\Reports\ReportResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dailyClosing')
                ->label('Rapports journaliers')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->url(fn () => ReportResource::getUrl('daily-closing')),
            Action::make('monthlySummary')
                ->label('Resumes mensuels')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->url(fn () => ReportResource::getUrl('monthly-summary')),
            CreateAction::make()->label('Nouveau rapport'),
        ];
    }
}