<x-filament-panels::page>
    <div class="space-y-6">

        <div style="max-width: 800px; margin: 0 auto; margin-bottom: 2rem;">
            <x-filament::section>
                <x-slot name="heading">
                    Nouveau dépôt
                </x-slot>

                {{ $this->form }}

                <div style="margin-top: 1rem;">
                    <x-filament::button wire:click="submitTransaction">
                        Enregistrer le dépôt
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>