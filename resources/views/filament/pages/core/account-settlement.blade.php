<x-filament-panels::page>
    <div class="space-y-6">

        <div style="max-width: 800px; margin: 0 auto; margin-bottom: 2rem;">
            <x-filament::section>
                <x-slot name="heading">
                    Soldé Compte
                </x-slot>

                {{ $this->form }}

                <div style="margin-top: 1rem;" x-data="{ saving: false }">
                    {{-- On ne fait plus wire:click="submitTransaction" directement.
                         Avant de soumettre, on va chercher 'selected' dans le
                         composant Alpine de la grille (case-grid.blade.php,
                         reperable via [data-case-grid-root]) et on le pousse
                         UNE SEULE FOIS vers Livewire, ici, au moment du clic -
                         jamais pendant que l'utilisateur coche des cases.
                         'saving' pilote le loader pendant tout l'appel, que
                         le depot reussisse ou soit rejete. Le reset visuel
                         (grille + montant), lui, ne se declenche que sur un
                         succes reel, via l'evenement "deposit-saved" envoye
                         par submitTransaction() cote serveur. --}}
                    <x-filament::button
                        type="button"
                        x-bind:disabled="saving"
                        x-on:click="
                            (async () => {
                                saving = true;
                                try {
                                    const grid = document.querySelector('[data-case-grid-root]');
                                    if (grid) {
                                        const tags = window.Alpine.$data(grid).selected ?? [];
                                        const statePath = grid.dataset.statePath;
                                        await $wire.set(statePath, tags);
                                    }
                                    await $wire.call('submitTransaction');
                                } finally {
                                    saving = false;
                                }
                            })()
                        "
                    >
                        <span x-show="!saving">Soldé compte</span>
                        <span x-show="saving" class="inline-flex items-center gap-2" style="display: none;">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Soldé...
                        </span>
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>