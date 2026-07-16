<x-filament-panels::page>
    <div class="flex items-center justify-center min-h-[70vh]">
        <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-8">

            <div class="mb-6 text-center">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                    Changement de mot de passe requis
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Vous devez définir un nouveau mot de passe avant de continuer.
                </p>
            </div>

            <form wire:submit="submit">
                {{ $this->form }}

                <div class="flex justify-center mt-2">
                    <x-filament::button type="submit" class="mt-1">
                        Changer le mot de passe
                    </x-filament::button>
                </div>
            </form>

        </div>
    </div>
</x-filament-panels::page>