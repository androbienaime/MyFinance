<x-filament-widgets::widget>
    <x-filament::tabs label="Devise">
        @foreach ($this->currencies as $currency)
            <x-filament::tabs.item
                :active="$this->activeCurrencyId === $currency->id"
                wire:click="selectCurrency({{ $currency->id }})"
                wire:key="currency-filter-tab-{{ $currency->id }}"
            >
                {{ $currency->iso_code }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
</x-filament-widgets::widget>