<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Filament\Concerns\HasCurrencyFilter;
use App\Models\Core\Account;
use App\Models\Core\Currency;
use App\Models\Core\Customer;
use App\Models\Core\Transaction;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use App\Enums\TransactionType;
use App\Filament\Concerns\HasReportsScope;

class GlobalStatsOverview extends Widget
{
    use HasCurrencyFilter, HasReportsScope;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';
    protected string $view = 'filament.widgets.global-stats-overview';

    public static function canView(): bool
    {
        return Auth::user()->can('reports.view');
    }

   #[Computed]
    public function stats(): array
    {
        $currency = $this->filterCurrencyId
            ? Currency::find($this->filterCurrencyId)
            : null;

        if (! $currency) {
            return [];
        }

        // Comptes : scope via l'employe QUI A CREE LE COMPTE (relation
        // directe Account->employee), pas via le client.
        $accountsQuery = Account::query()->where('currency_id', $currency->id);
        $this->applyReportsScope($accountsQuery);

        $activeAccounts = (clone $accountsQuery)->where('is_active', true)->count();
        $noEmptyAccounts = (clone $accountsQuery)->where('balance', '>', 0)->count();
        $totalAccounts = (clone $accountsQuery)->count();

        // Transactions : scope via l'employe qui a TRAITE la transaction
        // (relation directe Transaction->employee), independant de celui
        // qui a cree le compte ou le client.
        $depositsTodayQuery = Transaction::query()
            ->whereHas('account', fn ($q) => $q->where('currency_id', $currency->id))
            ->where('type', TransactionType::Deposit->value)
            ->whereDate('created_at', today());
        $this->applyReportsScope($depositsTodayQuery);
        $sumDepositsToday = $depositsTodayQuery->sum('amount');

        // Clients : scope via l'employe qui a ENREGISTRE le client
        // (relation directe Customer->employee).
        $clientsQuery = Customer::query()
            ->whereHas('accounts', fn ($q) => $q->where('currency_id', $currency->id));
        $this->applyReportsScope($clientsQuery);
        $clients = $clientsQuery->count();

        $txQuery = Transaction::query()
            ->whereHas('account', fn ($q) => $q->where('currency_id', $currency->id));
        $this->applyReportsScope($txQuery);

        $todayCount = (clone $txQuery)->whereDate('created_at', today())->count();
        $pendingCount = (clone $txQuery)->where('status', TransactionStatus::Pending->value)->count();

        return [
            ['label' => 'Clients', 'value' => number_format($clients), 'icon' => 'heroicon-o-user-group', 'color' => 'primary'],
            ['label' => 'Comptes actifs', 'value' => "{$activeAccounts}/{$totalAccounts}", 'icon' => 'heroicon-o-credit-card', 'color' => 'info', 'description' => "{$noEmptyAccounts} avec solde > 0"],
            ['label' => 'Dépôts aujourd\'hui', 'value' => number_format($sumDepositsToday, $currency->decimal_places ?? 2).' '.$currency->symbol, 'icon' => 'heroicon-o-banknotes', 'color' => 'success'],
            ['label' => "Transactions aujourd'hui", 'value' => number_format($todayCount), 'icon' => 'heroicon-o-calendar', 'color' => 'info'],
            ['label' => "En attente d'approbation", 'value' => number_format($pendingCount), 'icon' => 'heroicon-o-clock', 'color' => 'warning'],
        ];
    }
}