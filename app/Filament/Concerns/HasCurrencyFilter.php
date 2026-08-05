<?php

namespace App\Filament\Concerns;

use App\Models\Core\Currency;
use Livewire\Attributes\On;

trait HasCurrencyFilter
{
    public ?int $filterCurrencyId = null;

    /**
     * Hook Livewire declenche au montage de tout composant utilisant
     * ce trait (en plus de son propre mount()). On reprend la devise
     * memorisee en session par CurrencyFilterTabs, sinon la devise
     * par defaut du systeme.
     */
    public function mountHasCurrencyFilter(): void
    {
        $this->filterCurrencyId = session('reports.currency_filter')
            ?? Currency::default()?->id;
    }

    /**
     * Ecoute l'evenement diffuse par CurrencyFilterTabs quand
     * l'utilisateur change d'onglet, quel que soit le widget.
     */
    #[On('currency-filter-changed')]
    public function onCurrencyFilterChanged(int $currencyId): void
    {
        $this->filterCurrencyId = $currencyId;
    }
}