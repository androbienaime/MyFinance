<x-filament-panels::page>

    <style>
        /* ===================== Variables de couleur (clair / sombre) ===================== */
        .mf-wrap {
            --mf-border: #e5e7eb;
            --mf-text: #0f172a;
            --mf-text-muted: #64748b;
            --mf-surface: #ffffff;
            --mf-surface-alt: #f8fafc;

            --mf-primary: #4f46e5;
            --mf-primary-bg: #eef2ff;
            --mf-info: #0284c7;
            --mf-info-bg: #e0f2fe;
            --mf-success: #16a34a;
            --mf-success-bg: #dcfce7;
            --mf-danger: #dc2626;
            --mf-danger-bg: #fee2e2;
            --mf-warning: #d97706;
            --mf-warning-bg: #fef3c7;
        }
        .dark .mf-wrap {
            --mf-border: rgba(255,255,255,.08);
            --mf-text: #f1f5f9;
            --mf-text-muted: #94a3b8;
            --mf-surface: rgba(255,255,255,.03);
            --mf-surface-alt: rgba(255,255,255,.02);

            --mf-primary-bg: rgba(99,102,241,.14);
            --mf-info-bg: rgba(14,165,233,.14);
            --mf-success-bg: rgba(34,197,94,.14);
            --mf-danger-bg: rgba(239,68,68,.14);
            --mf-warning-bg: rgba(245,158,11,.14);
        }

        /* ===================== Filtres ===================== */
        .mf-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .mf-filters > div {
            flex: 1 1 180px;
            min-width: 160px;
        }
        .mf-label {
            display: block;
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--mf-text-muted);
            margin-bottom: .25rem;
        }

        /* ===================== Cartes KPI ===================== */
        .mf-kpi-grid {
            display: flex;
            flex-wrap: wrap;
            gap: .875rem;
            transition: opacity .15s ease;
        }
        .mf-kpi-card {
            flex: 1 1 150px;
            min-width: 150px;
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1rem;
            background: var(--mf-surface);
            border: 1px solid var(--mf-border);
            border-radius: .75rem;
        }
        .mf-kpi-icon {
            flex-shrink: 0;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: .6rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mf-kpi-icon svg { width: 1.15rem; height: 1.15rem; }
        .mf-kpi-label {
            font-size: .7rem;
            color: var(--mf-text-muted);
            margin: 0 0 .1rem;
        }
        .mf-kpi-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--mf-text);
            margin: 0;
            white-space: nowrap;
        }

        /* ===================== Tableaux (commun aux 3 sections) ===================== */
        .mf-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--mf-border);
            border-radius: .75rem;
        }
        .mf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8125rem;
            white-space: nowrap;
        }
        .mf-table thead th {
            position: sticky;
            top: 0;
            background: var(--mf-surface-alt);
            text-align: left;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--mf-text-muted);
            padding: .65rem .9rem;
            border-bottom: 1px solid var(--mf-border);
        }
        .mf-table th.mf-right, .mf-table td.mf-right { text-align: right; }
        .mf-table tbody td {
            padding: .65rem .9rem;
            border-bottom: 1px solid var(--mf-border);
            color: var(--mf-text);
        }
        .mf-table tbody tr:last-child td { border-bottom: none; }
        .mf-table tbody tr {
            transition: background-color .12s ease;
        }
        .mf-table tbody tr:hover {
            background: var(--mf-surface-alt);
        }
        .mf-table tbody tr.mf-empty-row td {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--mf-text-muted);
            white-space: normal;
        }

        .mf-cell-strong { font-weight: 600; color: var(--mf-text); }
        .mf-text-success { color: var(--mf-success); font-weight: 600; }
        .mf-text-danger { color: var(--mf-danger); font-weight: 600; }
        .mf-text-muted { color: var(--mf-text-muted); }

        /* entite (succursale / employe) avec icone ou avatar */
        .mf-entity {
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .mf-entity-icon {
            flex-shrink: 0;
            width: 1.9rem;
            height: 1.9rem;
            border-radius: .5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--mf-primary-bg);
            color: var(--mf-primary);
        }
        .mf-entity-icon svg { width: 1rem; height: 1rem; }

        .mf-avatar {
            flex-shrink: 0;
            width: 1.9rem;
            height: 1.9rem;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--mf-primary);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
        }

        .mf-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            background: var(--mf-surface-alt);
            color: var(--mf-text-muted);
            margin-right: .5rem;
        }
        .mf-rank.mf-rank-1 {
            background: var(--mf-warning-bg);
            color: var(--mf-warning);
        }

        /* badges */
        .mf-badge {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .mf-badge-success { background: var(--mf-success-bg); color: var(--mf-success); }
        .mf-badge-danger { background: var(--mf-danger-bg); color: var(--mf-danger); }
        .mf-badge-gray { background: var(--mf-surface-alt); color: var(--mf-text-muted); }

        /* barre de progression (volume relatif) */
        .mf-progress {
            width: 100%;
            min-width: 90px;
            height: .4rem;
            border-radius: 999px;
            background: var(--mf-surface-alt);
            overflow: hidden;
        }
        .mf-progress-bar {
            height: 100%;
            border-radius: 999px;
            background: var(--mf-primary);
        }

        /* pagination */
        .mf-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--mf-border);
            flex-wrap: wrap;
        }
        .mf-pagination-info { font-size: .75rem; color: var(--mf-text-muted); }
        .mf-pagination-buttons { display: flex; gap: .5rem; }
    </style>

    <div class="mf-wrap">

        {{-- ===================== FILTRES ===================== --}}
        <x-filament::section>
            <div class="mf-filters">
                <div>
                    <label class="mf-label">Du</label>
                    <x-filament::input type="date" wire:model.live="from" />
                </div>
                <div>
                    <label class="mf-label">Au</label>
                    <x-filament::input type="date" wire:model.live="until" />
                </div>

                @if ($this->branches->isNotEmpty())
                    <div>
                        <label class="mf-label">Succursale</label>
                        <x-filament::input.select wire:model.live="branchId">
                            <option value="">Toutes les succursales</option>
                            @foreach ($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </div>
                @endif

                <div>
                    <label class="mf-label">Employe</label>
                    <x-filament::input.select wire:model.live="employeeId">
                        <option value="">Tous les employes</option>
                        @foreach ($this->employees as $employee)
                            <option value="{{ $employee->id }}">{{ $this->employeeLabel($employee) }}</option>
                        @endforeach
                    </x-filament::input.select>
                </div>

                <div>
                    <label class="mf-label">Devise</label>
                    <x-filament::input.select wire:model.live="currencyId">
                        <option value="">Toutes les devises</option>
                        @foreach ($this->currencies as $currency)
                            <option value="{{ $currency->id }}">{{ $currency->iso_code }}</option>
                        @endforeach
                    </x-filament::input.select>
                </div>
            </div>
        </x-filament::section>

        {{-- ===================== KPI GLOBAUX ===================== --}}
        <div
            class="mf-kpi-grid"
            wire:loading.style="opacity: .5"
            wire:target="from,until,branchId,employeeId,currencyId"
            style="margin-top: 1.5rem;"
        >
            <div class="mf-kpi-card">
                <span class="mf-kpi-icon" style="background: var(--mf-primary-bg); color: var(--mf-primary);">
                    <x-filament::icon icon="heroicon-o-arrow-path-rounded-square" />
                </span>
                <div>
                    <p class="mf-kpi-label">Transactions</p>
                    <p class="mf-kpi-value">{{ number_format($this->summary['transaction_count']) }}</p>
                </div>
            </div>

            <div class="mf-kpi-card">
                <span class="mf-kpi-icon" style="background: var(--mf-info-bg); color: var(--mf-info);">
                    <x-filament::icon icon="heroicon-o-user-plus" />
                </span>
                <div>
                    <p class="mf-kpi-label">Nouveaux clients</p>
                    <p class="mf-kpi-value">{{ number_format($this->summary['new_customers']) }}</p>
                </div>
            </div>

            <div class="mf-kpi-card">
                <span class="mf-kpi-icon" style="background: var(--mf-info-bg); color: var(--mf-info);">
                    <x-filament::icon icon="heroicon-o-credit-card" />
                </span>
                <div>
                    <p class="mf-kpi-label">Nouveaux comptes</p>
                    <p class="mf-kpi-value">{{ number_format($this->summary['new_accounts']) }}</p>
                </div>
            </div>

            <div class="mf-kpi-card">
                <span class="mf-kpi-icon" style="background: var(--mf-success-bg); color: var(--mf-success);">
                    <x-filament::icon icon="heroicon-o-arrow-trending-up" />
                </span>
                <div>
                    <p class="mf-kpi-label">Depots</p>
                    <p class="mf-kpi-value">{{ number_format($this->summary['total_deposits'], 2) }}</p>
                </div>
            </div>

            <div class="mf-kpi-card">
                <span class="mf-kpi-icon" style="background: var(--mf-danger-bg); color: var(--mf-danger);">
                    <x-filament::icon icon="heroicon-o-arrow-trending-down" />
                </span>
                <div>
                    <p class="mf-kpi-label">Retraits</p>
                    <p class="mf-kpi-value">{{ number_format($this->summary['total_withdrawals'], 2) }}</p>
                </div>
            </div>

            @php $netPositive = $this->summary['net_flow'] >= 0; @endphp
            <div class="mf-kpi-card">
                <span class="mf-kpi-icon" style="background: var({{ $netPositive ? '--mf-success-bg' : '--mf-danger-bg' }}); color: var({{ $netPositive ? '--mf-success' : '--mf-danger' }});">
                    <x-filament::icon icon="heroicon-o-scale" />
                </span>
                <div>
                    <p class="mf-kpi-label">Flux net</p>
                    <p class="mf-kpi-value">{{ number_format($this->summary['net_flow'], 2) }}</p>
                </div>
            </div>
        </div>

        {{-- ===================== REPARTITION PAR SUCCURSALE ===================== --}}
        @if ($this->branchBreakdown->isNotEmpty())
            @php $maxTx = max(1, $this->branchBreakdown->max('transaction_count')); @endphp

            <div style="margin-top: 1.5rem;">
                <x-filament::section icon="heroicon-o-building-office-2" heading="Performance par succursale">
                    <div class="mf-table-wrap">
                        <table class="mf-table">
                            <thead>
                                <tr>
                                    <th>Succursale</th>
                                    <th class="mf-right">Nvx clients</th>
                                    <th class="mf-right">Nvx comptes</th>
                                    <th class="mf-right">Depots</th>
                                    <th class="mf-right">Retraits</th>
                                    <th class="mf-right">Flux net</th>
                                    <th>Volume</th>
                                    <th class="mf-right">Transactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->branchBreakdown as $row)
                                    <tr>
                                        <td>
                                            <div class="mf-entity">
                                                <span class="mf-entity-icon">
                                                    <x-filament::icon icon="heroicon-o-building-office-2" />
                                                </span>
                                                <span class="mf-cell-strong">{{ $row['label'] }}</span>
                                            </div>
                                        </td>
                                        <td class="mf-right">{{ $row['new_customers'] }}</td>
                                        <td class="mf-right">{{ $row['new_accounts'] }}</td>
                                        <td class="mf-right mf-text-success">{{ number_format($row['total_deposits'], 2) }}</td>
                                        <td class="mf-right mf-text-danger">{{ number_format($row['total_withdrawals'], 2) }}</td>
                                        <td class="mf-right">
                                            <span class="mf-badge {{ $row['net_flow'] >= 0 ? 'mf-badge-success' : 'mf-badge-danger' }}">
                                                {{ number_format($row['net_flow'], 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="mf-progress">
                                                <div class="mf-progress-bar" style="width: {{ $row['transaction_count'] / $maxTx * 100 }}%"></div>
                                            </div>
                                        </td>
                                        <td class="mf-right mf-cell-strong">{{ $row['transaction_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- ===================== REPARTITION PAR EMPLOYE ===================== --}}
        @if ($this->employeeBreakdown->isNotEmpty())
            <div style="margin-top: 1.5rem;">
                <x-filament::section icon="heroicon-o-users" heading="Classement par employe">
                    <div class="mf-table-wrap">
                        <table class="mf-table">
                            <thead>
                                <tr>
                                    <th>Employe</th>
                                    <th class="mf-right">Nvx clients</th>
                                    <th class="mf-right">Nvx comptes</th>
                                    <th class="mf-right">Depots</th>
                                    <th class="mf-right">Retraits</th>
                                    <th class="mf-right">Flux net</th>
                                    <th class="mf-right">Transactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->employeeBreakdown as $i => $row)
                                    <tr>
                                        <td>
                                            <div class="mf-entity">
                                                <span class="mf-rank {{ $i === 0 ? 'mf-rank-1' : '' }}">{{ $i + 1 }}</span>
                                                <span class="mf-avatar">
                                                    {{ collect(explode(' ', $row['label']))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                                                </span>
                                                <span class="mf-cell-strong">{{ $row['label'] }}</span>
                                            </div>
                                        </td>
                                        <td class="mf-right">{{ $row['new_customers'] }}</td>
                                        <td class="mf-right">{{ $row['new_accounts'] }}</td>
                                        <td class="mf-right mf-text-success">{{ number_format($row['total_deposits'], 2) }}</td>
                                        <td class="mf-right mf-text-danger">{{ number_format($row['total_withdrawals'], 2) }}</td>
                                        <td class="mf-right">
                                            <span class="mf-badge {{ $row['net_flow'] >= 0 ? 'mf-badge-success' : 'mf-badge-danger' }}">
                                                {{ number_format($row['net_flow'], 2) }}
                                            </span>
                                        </td>
                                        <td class="mf-right mf-cell-strong">{{ $row['transaction_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- ===================== DETAIL JOURNALIER ===================== --}}
        <div style="margin-top: 1.5rem;">
            <x-filament::section icon="heroicon-o-calendar-days" heading="Detail par jour">
                <div class="mf-table-wrap" style="max-height: 480px;">
                    <table class="mf-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="mf-right">Nvx clients</th>
                                <th class="mf-right">Nvx comptes</th>
                                <th class="mf-right">Depots</th>
                                <th class="mf-right">Retraits</th>
                                <th class="mf-right">Flux net</th>
                                <th class="mf-right">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->rows as $row)
                                <tr>
                                    <td class="mf-cell-strong">{{ $row['date'] }}</td>
                                    <td class="mf-right">{{ $row['new_customers'] }}</td>
                                    <td class="mf-right">{{ $row['new_accounts'] }}</td>
                                    <td class="mf-right mf-text-success">{{ number_format($row['total_deposits'], 2) }}</td>
                                    <td class="mf-right mf-text-danger">{{ number_format($row['total_withdrawals'], 2) }}</td>
                                    <td class="mf-right">
                                        <span class="mf-badge {{ $row['net_flow'] >= 0 ? 'mf-badge-success' : 'mf-badge-danger' }}">
                                            {{ number_format($row['net_flow'], 2) }}
                                        </span>
                                    </td>
                                    <td class="mf-right mf-cell-strong">{{ $row['transaction_count'] }}</td>
                                </tr>
                            @empty
                                <tr class="mf-empty-row">
                                    <td colspan="7">Aucune donnee sur cette periode.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mf-pagination">
                    <p class="mf-pagination-info">
                        Page {{ $this->rows->currentPage() }} / {{ max(1, $this->rows->lastPage()) }}
                        &middot; {{ $this->rows->total() }} jours
                    </p>
                    <div class="mf-pagination-buttons">
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-chevron-left"
                            wire:click="previousPage" :disabled="$this->rows->currentPage() <= 1">
                            Precedent
                        </x-filament::button>
                        <x-filament::button size="sm" color="gray" icon="heroicon-o-chevron-right" icon-position="after"
                            wire:click="nextPage" :disabled="$this->rows->currentPage() >= $this->rows->lastPage()">
                            Suivant
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>

    </div>

</x-filament-panels::page>