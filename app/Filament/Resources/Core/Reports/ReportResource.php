<?php

namespace App\Filament\Resources\Core\Reports;

use App\Enums\ReportStatus;
use App\Filament\Resources\Core\Reports\Pages\CreateReport;
use App\Filament\Resources\Core\Reports\Pages\DailyClosingReports;
use App\Filament\Resources\Core\Reports\Pages\EditReport;
use App\Filament\Resources\Core\Reports\Pages\ListReports;
use App\Filament\Resources\Core\Reports\Pages\MonthlySummaryReports;
use App\Filament\Resources\Core\Reports\Pages\ViewReport;
use App\Filament\Resources\Core\Reports\Schemas\ReportForm;
use App\Filament\Resources\Core\Reports\Schemas\ReportInfolist;
use App\Filament\Resources\Core\Reports\Tables\ReportsTable;
use App\Models\Core\Report;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('myfinance.reports');
    }

    public static function getNavigationLabel(): string
    {
        return 'Rapports';
    }

    public static function form(Schema $schema): Schema
    {
        return ReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'daily-closing' => DailyClosingReports::route('/daily-closing'),
            'monthly-summary' => MonthlySummaryReports::route('/monthly-summary'),
            'view' => ViewReport::route('/{record}'),
            'edit' => EditReport::route('/{record}/edit'),
        ];
    }

    /**
     * Le siege voit tous les rapports. Un utilisateur avec reports.view ou
     * reports.manage voit ceux de sa propre succursale. Un employe qui n'a
     * que reports.create ne voit que ses propres rapports.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $employeeId = $user->employee?->id;

        return parent::getEloquentQuery()->where(function (Builder $query) use ($user, $employeeId) {
            // Brouillons : uniquement le createur, ou quiconque a la
            // permission d'audit dediee - jamais concerne par le scope
            // all/branch/own ci-dessous.
            $query->where(function (Builder $q) use ($user, $employeeId) {
                $q->where('status', ReportStatus::Draft->value);

                if ($user->can('reports.view_all_drafts')) {
                    return; // aucune restriction supplementaire pour cet utilisateur
                }

                $q->where('employee_id', $employeeId);
            });

            // Rapports soumis : reutilise le scope existant all/branch/own
            // (voir HasReportsScope), jamais applique aux brouillons.
            $query->orWhere(function (Builder $q) use ($user, $employeeId) {
                $q->where('status', '!=', ReportStatus::Draft->value);
                static::applyNonDraftScope($q, $user, $employeeId);
            });
        });
    }

    /**
     * Meme convention que HasReportsScope (dashboard) : all > branch > own.
     * Si l'utilisateur n'a aucune de ces permissions, aucun rapport soumis
     * ne lui est visible (deny par defaut).
     */
    protected static function applyNonDraftScope(Builder $query, $user, ?int $employeeId): void
    {
        if ($user->can('reports.view.all')) {
            return; // aucune restriction
        }

        if ($user->can('reports.view.branch')) {
            $branchId = $user->currentBranchId();

            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
            return;
        }

        if ($user->can('reports.view.own')) {
            $query->where('employee_id', $employeeId);
            return;
        }

        // Aucune permission de visualisation : deny explicite, pas de
        // fallback silencieux qui laisserait tout passer par erreur.
        $query->whereRaw('1 = 0');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()
            ->where('status', \App\Enums\ReportStatus::Pending->value)
            ->count() ?: null;
    }
}