<x-filament-widgets::widget>

    <style>
        .gso-wrap {
            --gso-border: #e5e7eb;
            --gso-text: #0f172a;
            --gso-text-muted: #64748b;
            --gso-surface: #ffffff;

            --gso-primary: #4f46e5;
            --gso-primary-bg: #eef2ff;
            --gso-info: #0284c7;
            --gso-info-bg: #e0f2fe;
            --gso-success: #16a34a;
            --gso-success-bg: #dcfce7;
            --gso-warning: #d97706;
            --gso-warning-bg: #fef3c7;
        }
        .dark .gso-wrap {
            --gso-border: rgba(255,255,255,.08);
            --gso-text: #f1f5f9;
            --gso-text-muted: #94a3b8;
            --gso-surface: rgba(255,255,255,.03);

            --gso-primary-bg: rgba(99,102,241,.14);
            --gso-info-bg: rgba(14,165,233,.14);
            --gso-success-bg: rgba(34,197,94,.14);
            --gso-warning-bg: rgba(245,158,11,.14);
        }

        .gso-kpi-grid {
            display: flex;
            flex-wrap: wrap;
            gap: .875rem;
            transition: opacity .15s ease;
        }
        .gso-kpi-card {
            flex: 1 1 150px;
            min-width: 150px;
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1rem;
            background: var(--gso-surface);
            border: 1px solid var(--gso-border);
            border-radius: .75rem;
        }
        .gso-kpi-icon {
            flex-shrink: 0;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: .6rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .gso-kpi-icon svg { width: 1.15rem; height: 1.15rem; }
        .gso-kpi-label {
            font-size: .7rem;
            color: var(--gso-text-muted);
            margin: 0 0 .1rem;
        }
        .gso-kpi-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--gso-text);
            margin: 0;
            white-space: nowrap;
        }
        .gso-empty {
            font-size: .8125rem;
            color: var(--gso-text-muted);
            padding: 1rem 0;
        }
    </style>

    <div class="gso-wrap">
        <div
            class="gso-kpi-grid"
            wire:loading.style="opacity: .5"
            wire:target="onCurrencyFilterChanged"
        >
            @forelse ($this->stats as $stat)
                <div class="gso-kpi-card">
                    <span
                        class="gso-kpi-icon"
                        style="background: var(--gso-{{ $stat['color'] }}-bg); color: var(--gso-{{ $stat['color'] }});"
                    >
                        <x-filament::icon :icon="$stat['icon']" />
                    </span>
                    <div>
                        <p class="gso-kpi-label">{{ $stat['label'] }}</p>
                        <p class="gso-kpi-value">{{ $stat['value'] }}</p>
                        @if (isset($stat['description']))
                            <p class="gso-kpi-description">{{ $stat['description'] }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="gso-empty">Aucune devise selectionnee.</p>
            @endforelse
        </div>
    </div>

</x-filament-widgets::widget>