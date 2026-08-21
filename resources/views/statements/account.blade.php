{{-- resources/views/statements/account.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé de compte - {{ $account->code }}</title>
    <style>
        @page { margin: 24px; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .print-btn { margin-bottom: 15px; text-align: right; }
        .print-btn button {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            padding: 9px 20px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.3px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
        }
        .print-btn button:hover { background: #1d4ed8; }
        @media print { .print-btn { display: none; } }

        /* Cadre général du document */
        .sheet {
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 24px 28px;
        }

        /* En-tête */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header .brand-mark {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            /* background: linear-gradient(135deg, #2563eb, #1d4ed8); */
            color: #fff;
            font-weight: bold;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header h1 {
            font-size: 19px;
            margin: 0;
            color: #0f172a;
            letter-spacing: 0.3px;
        }
        .header .brand-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 1px;
        }
        .header .meta-right {
            text-align: right;
            font-size: 10.5px;
            color: #64748b;
        }

        /* Bloc informations du compte, encadré en carte */
        .info-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 22px;
        }
        .info-item { }
        .info-label {
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin: 0 0 2px;
        }
        .info-value {
            font-size: 12.5px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .info-value.balance {
            color: #2563eb;
            font-size: 14px;
        }

        .section-title {
            font-size: 13.5px;
            font-weight: bold;
            color: #0f172a;
            margin: 22px 0 10px;
            padding-left: 10px;
            border-left: 4px solid #2563eb;
        }

        /* Tableau des transactions, encadré */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        thead tr { background: #2563eb; }
        th {
            color: #fff;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 9px 10px;
            text-align: left;
            border: none;
        }
        td {
            border: none;
            border-bottom: 1px solid #edf1f7;
            padding: 8px 10px;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }

        .amount-credit { color: #16a34a; font-weight: bold; }
        .amount-debit { color: #dc2626; font-weight: bold; }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 9.5px;
            background: #e2e8f0;
            color: #334155;
        }

        /* Grille des cases (livret) */
        .case-month {
            margin-bottom: 15px;
            page-break-inside: avoid;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
            background: #f8fafc;
        }
        .case-month-label {
            font-size: 12px;
            text-align: center;
            color: #64748b;
            margin: 6px 0 0;
            font-weight: bold;
        }
        .case-table { width: 100%; border-collapse: collapse; }
        .case-table td {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            text-align: center;
            padding: 7px 3px;
            font-size: 12px;
            font-weight: bold;
            width: 20%;
            background: #fff;
        }
        .case-table td.paid {
            background: #2563eb;
            color: #fff;
            border-color: #1d4ed8;
        }
        .case-table td .case-amount { display: block; font-size: 9px; font-weight: normal; opacity: 0.85; }
        .case-table td .case-date { display: block; font-size: 9px; font-weight: normal; opacity: 0.85; }
        .case-grid-wrapper {
            width: 75%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .case-summary {
            font-size: 10.5px;
            color: #334155;
            margin-top: 10px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 8px 12px;
            display: inline-block;
        }

        /* Pied de page */
        .footer {
            margin-top: 26px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 9.5px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="print-btn">
        <button onclick="window.print()">Imprimer</button>
    </div>

    <div class="sheet">

        <div class="header">
            <div class="brand">
                <div class="brand-mark">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ env('APP_NAME') }}" class="brand-logo" width="68" height="68">
                </div>
                <div>
                    <h1>Relevé de compte</h1>
                    <p class="brand-sub">{{ env('APP_NAME') }}</p>
                </div>
            </div>
            <div class="meta-right">
                Généré le {{ $generatedAt->format('d/m/Y H:i') }}
            </div>
        </div>

        <div class="info-card">
            <div class="info-item">
                <p class="info-label">Compte</p>
                <p class="info-value">{{ $account->code }} ({{ $account->typeOfAccount->name }})</p>
            </div>
            <div class="info-item">
                <p class="info-label">Titulaire</p>
                <p class="info-value">{{ $account->customer?->person?->full_name ?? 'N/A' }}</p>
            </div>
            <div class="info-item">
                <p class="info-label">Devise</p>
                <p class="info-value">{{ translate_currency_name($account->currency->name) }}</p>
            </div>
            <div class="info-item">
                <p class="info-label">Solde actuel</p>
                <p class="info-value balance">{{ number_format($account->balance, 2) }} {{ $account->currency->iso_code }}</p>
            </div>
            <div class="info-item" style="grid-column: 1 / -1;">
                <p class="info-label">Période</p>
                <p class="info-value">{{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="section-title">Historique des transactions</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Montant</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $t)
                    <tr>
                        <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $t->code }}</td>
                        <td>{{ $t->type->label() }}</td>
                        <td class="{{ in_array($t->direction?->value, ['credit']) || in_array($t->type->value, ['deposit']) ? 'amount-credit' : 'amount-debit' }}">
                            {{ $t?->currency?->iso_code }} {{ number_format($t->amount, 2) }}
                        </td>
                        <td><span class="status-badge">{{ $t->status->value }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; color:#94a3b8;">Aucune transaction sur cette période.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($caseGrid !== null)
            <div class="section-title">Grille des cases</div>

            <div class="case-grid-wrapper">
                @foreach ($caseGrid['months'] as $month => $cases)
                    <div class="case-month">
                        <table class="case-table">
                            @foreach (array_chunk($cases, 5) as $row)
                                <tr>
                                    @foreach ($row as $case)
                                        <td class="{{ $case['is_paid'] ? 'paid' : '' }}">
                                            {{ $case['number'] }}
                                            <span class="case-amount">({{ number_format($case['amount'], 0) }})</span>
                                            @if ($case['is_paid'] && $case['paid_at'])
                                                <span class="case-date">{{ $case['paid_at']->format('d/m/y') }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>
                        <p class="case-month-label">Mois {{ $month }}</p>
                    </div>
                @endforeach
            </div>

            <div class="case-summary">
                <strong>{{ $caseGrid['paid_count'] }} / {{ $caseGrid['total_cases'] }}</strong> cases payées
            </div>
        @endif

        <div class="footer">
            Document généré automatiquement par {{ env('APP_NAME') }} — {{ $generatedAt->format('d/m/Y H:i') }}
        </div>

    </div>
</body>
</html>

