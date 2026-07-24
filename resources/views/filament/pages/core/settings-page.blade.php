{{-- resources/views/filament/pages/settings-page.blade.php --}}
<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="fi-form-actions mt-6 flex items-center gap-3">
            <x-filament::button
                type="submit"
                icon="heroicon-o-check"
            >
                Enregistrer les paramètres
            </x-filament::button>

            <x-filament::button
                type="button"
                color="gray"
                wire:click="$refresh"
            >
                Annuler
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>