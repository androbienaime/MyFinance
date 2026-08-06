<?php

namespace App\Filament\Pages\Core;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Core\Account;
use App\Models\Core\Branch;
use App\Models\Core\Currency;
use App\Models\Core\Customer;
use App\Models\Core\Employee;
use App\Models\Core\Transaction;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;
use Livewire\Attributes\Computed;

class DailyActivityAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Activite';
    protected static ?string $title = 'Activite';
    protected string $view = 'filament.pages.core.daily-activity-analytics';

    public static function getNavigationGroup(): string
    {
        return __('myfinance.reports');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('daily_activity_analytics.view') ?? false;
    }

    // Filtres, lies via wire:model.live dans la vue
    public ?int $branchId = null;
    public ?int $employeeId = null;
    public ?int $currencyId = null;
    public string $from;
    public string $until;

    public int $page = 1;

    protected int $perPage = 10;

    public function mount(): void
    {
        // $this->from = Carbon::today()->subDays(29)->toDateString();
        $this->from = Carbon::today()->toDateString();
        $this->until = Carbon::today()->toDateString();

        // Un utilisateur non-siege est automatiquement cantonne a sa succursale
        if (! auth()->user()->isHeadOffice()) {
            $this->branchId = auth()->user()->currentBranchId();
        }
    }

    // On revient toujours a la page 1 quand un filtre change
    public function updatedBranchId(): void
    {
        // Un employe appartient a une seule succursale : si on change de
        // succursale, l'employe selectionne n'est plus forcement valide
        $this->employeeId = null;
        $this->page = 1;
    }

    public function updatedEmployeeId(): void
    {
        $this->page = 1;
    }

    public function updatedCurrencyId(): void
    {
        $this->page = 1;
    }

    public function updatedFrom(): void
    {
        $this->page = 1;
    }

    public function updatedUntil(): void
    {
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function getBranchesProperty()
    {
        return auth()->user()->isHeadOffice()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();
    }

    /**
     * Employes de la succursale selectionnee (ou de la succursale courante
     * pour un utilisateur non-siege). Sert au filtre ET a la repartition
     * par employe.
     */
    #[Computed]
    public function getEmployeesProperty(): Collection
    {
        return Employee::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when(
                ! auth()->user()->isHeadOffice(),
                fn ($q) => $q->where('branch_id', auth()->user()->currentBranchId())
            )
            ->orderBy('firstname')
            ->get();
    }

    public function getCurrenciesProperty()
    {
        return Currency::orderBy('iso_code')->get();
    }

    /**
     * Un compte (et donc une transaction) appartient a un client, et c'est
     * le CLIENT qui est rattache a un employe/une succursale — jamais le
     * compte directement. On passe donc toujours par la relation indiquee
     * (`employee` pour Customer, `customer.employee` pour Account,
     * `account.customer.employee` pour Transaction).
     */
    protected function scopeByEmployeeOrBranch($query, string $relation, ?int $branchId, ?int $employeeId): void
    {
        $query
            ->when($employeeId, fn ($q) => $q->whereHas(
                $relation,
                fn ($eq) => $eq->where('id', $employeeId)
            ))
            ->when(! $employeeId && $branchId, fn ($q) => $q->whereHas(
                $relation,
                fn ($eq) => $eq->where('branch_id', $branchId)
            ));
    }

    /**
     * Calcule les indicateurs cles pour une date precise (rapport journalier)
     * ou pour toute la periode [from, until] si $day est null (repartitions
     * par succursale / employe). $branchId et $employeeId permettent de
     * calculer la ligne d'une succursale ou d'un employe en particulier,
     * independamment des filtres globaux de la page.
     */
    protected function computeMetrics(?Carbon $day, ?int $branchId, ?int $employeeId): array
    {
        $customerQuery = Customer::query();
        $accountQuery = Account::query();
        $txQuery = Transaction::query()->where('status', TransactionStatus::Completed->value);

       if ($day) {
        $customerQuery->whereDate('created_at', $day);
        $accountQuery->whereDate('created_at', $day);
        $txQuery->whereDate('transactions.created_at', $day);
        } else {
            // On force le debut et la fin de journee pour couvrir la
            // plage complete, sinon "until" est interprete comme minuit
            // et exclut toutes les transactions de la journee finale.
            $from = Carbon::parse($this->from)->startOfDay();
            $until = Carbon::parse($this->until)->endOfDay();

            $customerQuery->whereBetween('created_at', [$from, $until]);
            $accountQuery->whereBetween('created_at', [$from, $until]);
            $txQuery->whereBetween('transactions.created_at', [$from, $until]);
        }

        if ($this->currencyId) {
            $accountQuery->where('currency_id', $this->currencyId);
            $txQuery->whereHas('account', fn ($aq) => $aq->where('currency_id', $this->currencyId));
        }

        $this->scopeByEmployeeOrBranch($customerQuery, 'employee', $branchId, $employeeId);
        $this->scopeByEmployeeOrBranch($accountQuery, 'customer.employee', $branchId, $employeeId);
        $this->scopeByEmployeeOrBranch($txQuery, 'account.customer.employee', $branchId, $employeeId);

        $deposits = (clone $txQuery)->where('type', TransactionType::Deposit->value)->sum('amount');
        $withdrawals = (clone $txQuery)->where('type', TransactionType::Withdrawal->value)->sum('amount');

        return [
            'new_customers' => $customerQuery->count(),
            'new_accounts' => $accountQuery->count(),
            'total_deposits' => (float) $deposits,
            'total_withdrawals' => (float) $withdrawals,
            'net_flow' => (float) ($deposits - $withdrawals),
            'transaction_count' => (clone $txQuery)->count(),
        ];
    }

    /**
     * Tableau journalier, pagine : on ne calcule les indicateurs que pour
     * les jours reellement affiches sur la page courante.
     */
    #[Computed]
    public function getRowsProperty(): LengthAwarePaginator
    {
        $allDays = array_reverse(
            iterator_to_array(Carbon::parse($this->from)->toPeriod(Carbon::parse($this->until)))
        );

        $total = count($allDays);
        $page = max(1, $this->page);
        $daysForPage = array_slice($allDays, ($page - 1) * $this->perPage, $this->perPage);

        $rows = collect($daysForPage)->map(fn (Carbon $day) => array_merge(
            ['date' => $day->format('d/m/Y')],
            $this->computeMetrics($day, $this->branchId, $this->employeeId)
        ))->all();

        return new Paginator(
            items: $rows,
            total: $total,
            perPage: $this->perPage,
            currentPage: $page,
        );
    }

    /**
     * Totaux agreges sur toute la periode filtree (succursale + employe +
     * devise), utilises pour les cartes KPI en haut de page.
     */
    #[Computed]
    public function getSummaryProperty(): array
    {
        return $this->computeMetrics(day: null, branchId: $this->branchId, employeeId: $this->employeeId);
    }

    /**
     * Repartition sur toute la periode, par succursale. Reserve au siege :
     * un utilisateur de succursale voit deja tout via les filtres.
     */
    public function getBranchBreakdownProperty(): Collection
    {
        if (! auth()->user()->isHeadOffice()) {
            return collect();
        }

        return Branch::query()
            ->where('is_active', true)
            ->when($this->branchId, fn ($q) => $q->where('id', $this->branchId))
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch) => array_merge(
                ['label' => $branch->name],
                $this->computeMetrics(day: null, branchId: $branch->id, employeeId: null)
            ))
            ->sortByDesc('transaction_count')
            ->values();
    }

    /**
     * Repartition sur toute la periode, par employe (de la succursale
     * filtree, ou toutes succursales confondues pour le siege). Les
     * employes sans aucune activite sur la periode sont masques.
     */
    public function getEmployeeBreakdownProperty(): Collection
    {
        return $this->employees
            ->map(fn (Employee $employee) => array_merge(
                ['label' => $this->employeeLabel($employee)],
                $this->computeMetrics(day: null, branchId: null, employeeId: $employee->id)
            ))
            ->filter(fn ($row) => $row['new_customers'] || $row['new_accounts'] || $row['transaction_count'])
            ->sortByDesc('transaction_count')
            ->values();
    }

    /**
     * A adapter selon les colonnes reelles de votre modele Employee
     * (first_name/last_name, name, full_name...).
     */
    protected function employeeLabel(Employee $employee): string
    {
        if (! empty($employee->full_name)) {
            return $employee->full_name;
        }

        $name = trim(($employee->firstname ?? '').' '.($employee->lastname ?? ''));

        return $name !== '' ? $name : ($employee->name ?? "Employe #{$employee->id}");
    }
}