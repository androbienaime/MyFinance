<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Nouveau depot</x-slot>

            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button wire:click="submitTransaction">
                    Enregistrer le depot
                </x-filament::button>
            </div>
        </x-filament::section>

        {{ $this->table }}
    </div>
</x-filament-panels::page>