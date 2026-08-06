<?php

namespace App\Filament\Widgets;

use App\Models\Core\Currency;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class CurrencyFilterTabs extends Widget
{
    // Toujours affiche en premier, au-dessus des autres widgets
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.currency-filter-tabs';

    public ?int $activeCurrencyId = null;

    public function mount(): void
    {
        $this->activeCurrencyId = session('reports.currency_filter')
            ?? Currency::default()?->id
            ?? $this->currencies->first()?->id;
    }

    #[Computed]
    public function currencies(): Collection
    {
        return Currency::query()->where('is_active', true)->orderBy('iso_code')->get();
    }

    public function selectCurrency(int $currencyId): void
    {
        $this->activeCurrencyId = $currencyId;

        // Persiste le choix pour les prochains chargements de page
        session(['reports.currency_filter' => $currencyId]);

        // Previent tous les widgets ecoutant #[On('currency-filter-changed')]
        $this->dispatch('currency-filter-changed', currencyId: $currencyId);
    }
}