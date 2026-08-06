<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasCurrencyFilter;
use App\Filament\Concerns\HasReportsScope;
use App\Models\Core\Account;
use App\Models\Core\Currency;
use App\Models\Core\TypeOfAccount;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AccountsByTypeChart extends ChartWidget
{
    use HasCurrencyFilter;
    use HasReportsScope;

    protected ?string $heading = 'Comptes par type';

    protected static ?int $sort = 4;

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
        $currency = $this->filterCurrencyId
            ? Currency::find($this->filterCurrencyId)
            : null;

        $query = Account::query()
            ->selectRaw('type_of_account_id, COUNT(*) as total')
            ->when($currency, fn ($q) => $q->where('currency_id', $currency->id));

        // Restreint aux comptes crees par l'employe lui-meme, ou par
        // sa succursale, selon son niveau d'acces.
        $this->applyReportsScope($query);

        $counts = $query
            ->groupBy('type_of_account_id')
            ->pluck('total', 'type_of_account_id');

        $types = TypeOfAccount::orderBy('id')->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($types as $type) {
            $total = $counts[$type->id] ?? 0;

            if ($total === 0) {
                continue;
            }

            $labels[] = $type->name;
            $data[] = $total;
            $colors[] = $this->colorFor($type, $types);
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function colorFor(TypeOfAccount $type, Collection $types): string
    {
        $palette = [
            '#4F46E5', '#0284C7', '#16A34A', '#D97706',
            '#DC2626', '#7C3AED', '#0891B2', '#DB2777',
        ];

        $index = $types->search(fn ($t) => $t->id === $type->id);

        return $palette[$index % count($palette)];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}