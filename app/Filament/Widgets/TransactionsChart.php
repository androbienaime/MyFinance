<?php

namespace App\Filament\Widgets;

use App\Models\Core\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TransactionsChart extends ChartWidget
{
    protected ?string $heading = 'Depots et retraits (30 derniers jours)';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return Auth::user()->can('reports.view');
    }

    protected function getData(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $rows = Transaction::query()
            ->depositsAndWithdrawalsByDay($start, $end)
            ->get()
            ->keyBy('transaction_date');

        $labels = [];
        $deposits = [];
        $withdrawals = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $deposits[] = (float) ($rows[$key]->deposit_sum ?? 0);
            $withdrawals[] = (float) ($rows[$key]->withdrawal_sum ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Depots',
                    'data' => $deposits,
                    'borderColor' => '#0F6E56',
                    'backgroundColor' => 'rgba(15, 110, 86, 0.1)',
                ],
                [
                    'label' => 'Retraits',
                    'data' => $withdrawals,
                    'borderColor' => '#DC2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}