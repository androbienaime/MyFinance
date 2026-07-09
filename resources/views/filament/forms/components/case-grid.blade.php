@php
    $duration = (int) ($get('case_duration') ?? 0);
    $price = (float) ($get('case_price') ?? 0);
    $paidTags = $get('paid_tags') ?? [];
    $statePath = $field->getStatePath();
@endphp

<div
    {{-- Attribut stable, non templated par une valeur qui peut changer
         (contrairement au wire:key) : c'est ce que le bouton "Enregistrer"
         utilise pour retrouver ce composant et lire 'selected' au moment
         de la soumission, via Alpine.$data(). --}}
    data-case-grid-root
    data-state-path="{{ $statePath }}"
    wire:key="case-grid-{{ $get('account_code') }}-{{ $duration }}"
    x-data="caseGrid({
        duration: {{ $duration }},
        price: {{ $price }},
        paid: @js($paidTags),
    })"
    x-on:generate-cases.window="generate($event.detail.amount)"
>
    {{-- Champ "Nombres" : juste sous le Montant. Cliquer une case l'ajoute
         ici, taper ici selectionne la case correspondante (meme tableau
         "selected" des deux cotes, donc synchro automatique en local,
         sans aucune requete reseau). --}}
    <div class="mb-4">
        <div class="flex items-center justify-between">
            <label class="text-xs font-bold uppercase text-gray-600">Nombres</label>
            <button
                type="button"
                @click="reset()"
                x-show="selected.length > 0"
                class="text-xs font-medium text-red-600 hover:text-red-700 hover:underline focus:outline-none"
            >
                Réinitialiser
            </button>
        </div>
        <div class="appearance-none block w-full bg-gray-200 text-gray-700 border rounded p-2">
            <div class="tags-input flex flex-wrap gap-1.5">
                <template x-for="(n, index) in selected" :key="n">
                    <span
                        class="inline-flex items-center gap-1 rounded-md px-2.5 bg-blue-500 py-1 text-xs font-medium text-white shadow-sm"
                        :class="tagColor(index)"
                    >
                        <span x-text="n"></span>

                        <button
                            type="button"
                            @click="toggle(n)"
                            class="ml-0.5 text-white/90 hover:text-white focus:outline-none"
                        >
                            &times;
                        </button>
                    </span>
                </template>

                <input
                    class="tags-input-text"
                    placeholder="Ajouter des Casiers"
                    x-model="manualInput"
                    @keydown.enter.prevent="addManual()"
                    @keydown.space.prevent="addManual()"
                >
            </div>
        </div>
        <div class="text-right text-sm font-semibold mt-1" x-show="selected.length > 0">
            Total : <span x-text="total"></span> HTG
        </div>
    </div>

    {{-- Grille du livret : une table par echelon (mois), 30 cases chacune --}}
    <div class="grid gap-4 md:grid-cols-2">
        <template x-for="month in months" :key="month">
            <div class="w-full overflow-x-auto">
                <table class="mx-auto border-collapse border border-gray-400 w-full text-sm">
                    <tbody>
                        <template x-for="rowIndex in Array.from({ length: 6 }, (_, i) => i)" :key="month + '-' + rowIndex">
                            <tr>
                                <template x-for="n in casesInMonth(month).slice(rowIndex * 5, rowIndex * 5 + 5)" :key="n">
                                    <td
                                        @click="toggle(n)"
                                        class="border border-gray-400 px-3 py-2 text-center select-none"
                                        :class="{
                                            'bg-blue-600 text-white cursor-not-allowed': paid.includes(n),
                                            'bg-yellow-500 text-white cursor-pointer': !paid.includes(n) && selected.includes(n),
                                            'cursor-pointer hover:bg-yellow-500 hover:text-white': !paid.includes(n) && !selected.includes(n),
                                        }"
                                    >
                                        <span x-text="n"></span>
                                        <span class="text-[10px]" x-text="'(' + (n * price) + ')'"></span>
                                        <template x-if="paid.includes(n) || selected.includes(n)">
                                            <span class="bi bi-check2-circle ml-1"></span>
                                        </template>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p class="text-center text-xs text-gray-500 mt-1">
                    Mois <span x-text="month"></span>
                </p>
            </div>
        </template>
    </div>
</div>