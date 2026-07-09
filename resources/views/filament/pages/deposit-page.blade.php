<x-filament-panels::page>
    <div class="space-y-6">

        <div style="max-width: 800px; margin: 0 auto; margin-bottom: 2rem;">
            <x-filament::section>
                <x-slot name="heading">
                    Nouveau dépôt
                </x-slot>

                {{ $this->form }}

                <div style="margin-top: 1rem;">
                    {{-- On ne fait plus wire:click="submitTransaction" directement.
                         Avant de soumettre, on va chercher 'selected' dans le
                         composant Alpine de la grille (case-grid.blade.php,
                         reperable via [data-case-grid-root]) et on le pousse
                         UNE SEULE FOIS vers Livewire, ici, au moment du clic -
                         jamais pendant que l'utilisateur coche des cases. --}}
                    <x-filament::button
                        type="button"
                        x-on:click="
                            (() => {
                                const grid = document.querySelector('[data-case-grid-root]');
                                const tags = grid ? (window.Alpine.$data(grid).selected ?? []) : [];
                                const statePath = grid ? grid.dataset.statePath : 'data.tags';

                                if (! grid) {
                                    // Compte sans systeme de cases : rien a
                                    // synchroniser, on soumet directement.
                                    $wire.call('submitTransaction');
                                    return;
                                }

                                $wire.set(statePath, tags).then(() => {
                                    $wire.call('submitTransaction');
                                });
                            })()
                        "
                    >
                        Enregistrer le dépôt
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>