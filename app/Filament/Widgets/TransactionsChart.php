<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Filament\Concerns\HasCurrencyFilter;
use App\Filament\Concerns\HasReportsScope;
use App\Models\Core\Currency;
use App\Models\Core\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TransactionsChart extends ChartWidget
{
    use HasCurrencyFilter;
    use HasReportsScope;

    protected ?string $heading = 'Depots et retraits (12 derniers mois)';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return Auth::user()->can('reports.view');
    }

    public function updatedFilterCurrencyId(): void
    {
        $this->dispatch('updateChartData', data: $this->getData());
    }

    protected function getData(): array
    {
        $end = Carbon::now()->endOfMonth();
        $start = Carbon::now()->subMonths(11)->startOfMonth();

        $currency = $this->filterCurrencyId
            ? Currency::find($this->filterCurrencyId)
            : null;

        $query = Transaction::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as deposit_sum', [TransactionType::Deposit->value])
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as withdrawal_sum', [TransactionType::Withdrawal->value])
            ->where('status', TransactionStatus::Completed->value)
            ->whereBetween('created_at', [$start, $end])
            ->when($currency, fn ($q) => $q->whereHas(
                'account',
                fn ($aq) => $aq->where('currency_id', $currency->id)
            ));

        // Restreint aux transactions traitees par l'employe lui-meme,
        // ou par sa succursale, selon son niveau d'acces.
        $this->applyReportsScope($query);

        $rows = $query
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $labels = [];
        $deposits = [];
        $withdrawals = [];

        for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
            $key = $date->format('Y-m');
            $labels[] = $date->format('m/Y');
            $deposits[] = (float) ($rows[$key]->deposit_sum ?? 0);
            $withdrawals[] = (float) ($rows[$key]->withdrawal_sum ?? 0);
        }

        $palette = $this->monthlyPalette(count($labels));

        return [
            'datasets' => [
                [
                    'label' => 'Depots',
                    'data' => $deposits,
                    'backgroundColor' => $palette,
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Retraits',
                    'data' => $withdrawals,
                    'backgroundColor' => array_map(
                        fn (string $color) => $this->darken($color, .25),
                        $palette
                    ),
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function monthlyPalette(int $count): array
    {
        $colors = [];

        for ($i = 0; $i < $count; $i++) {
            $hue = (int) round(($i * (360 / max($count, 1))));
            $colors[] = "hsl({$hue}, 70%, 55%)";
        }

        return $colors;
    }

    protected function darken(string $hsl, float $amount): string
    {
        preg_match('/hsl\((\d+), (\d+)%, (\d+)%\)/', $hsl, $matches);

        [$_, $hue, $saturation, $lightness] = $matches;

        $newLightness = max(15, (int) $lightness - ($amount * 100));

        return "hsl({$hue}, {$saturation}%, {$newLightness}%)";
    }

    protected function getType(): string
    {
        return 'bar';
    }
}