<?php

namespace App\Filament\Widgets\Core;

use App\Enums\TransactionStatus;
use App\Models\Core\Account;
use App\Models\Core\Customer;
use App\Models\Core\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class GlobalStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()->can('reports.view');
    }

    protected function getStats(): array
    {
        $totalBalance = Account::where('is_active', true)->sum('balance');

        return [
            Stat::make('Clients', Customer::count())
                ->icon('heroicon-o-user-group'),

            Stat::make('Comptes actifs', Account::where('is_active', true)->count())
                ->icon('heroicon-o-credit-card'),

            Stat::make('Solde total', number_format($totalBalance, 2).' HTG')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Transactions aujourd\'hui', Transaction::whereDate('created_at', today())->count())
                ->icon('heroicon-o-calendar'),

            Stat::make('En attente d\'approbation', Transaction::where('status', TransactionStatus::Pending->value)->count())
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}