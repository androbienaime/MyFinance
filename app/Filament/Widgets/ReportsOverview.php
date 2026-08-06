<?php

namespace App\Filament\Widgets;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Core\Report;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ReportsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return Auth::user()->can('reports.manage');
    }

    protected function getStats(): array
    {
        $baseQuery = $this->scopedQuery();

        $pending = (clone $baseQuery)->where('status', ReportStatus::Pending->value)->count();

        $manualThisMonth = (clone $baseQuery)
            ->where('type', ReportType::Manual->value)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $automaticThisMonth = (clone $baseQuery)
            ->where('type', ReportType::Automatic->value)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $openDiscrepancies = (clone $baseQuery)
            ->where('category', ReportCategory::CashDiscrepancy->value)
            ->where('status', ReportStatus::Pending->value)
            ->count();

        return [
            Stat::make('Rapports en attente', $pending)
                ->color($pending > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),

            Stat::make('Rapports manuels ce mois', $manualThisMonth)
                ->icon('heroicon-o-pencil-square'),

            Stat::make('Rapports automatiques ce mois', $automaticThisMonth)
                ->icon('heroicon-o-cog-6-tooth'),

            Stat::make('Ecarts de caisse non revus', $openDiscrepancies)
                ->color($openDiscrepancies > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }

    protected function scopedQuery()
    {
        $user = Auth::user();
        $query = Report::query();

        if ($user->isHeadOffice()) {
            return $query;
        }

        return $query->where('branch_id', $user->currentBranchId());
    }
}