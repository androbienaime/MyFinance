<?php

namespace App\Http\Controllers;

use App\Models\Core\Account;
use App\Models\Core\Customer;
use App\Services\StatementBalanceCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatementController extends Controller
{
    public function __construct(private StatementBalanceCalculator $calculator) {}

    public function printAccount(Request $request, Account $account)
    {
        abort_unless(auth()->user()->can('view', $account), 403);

        return view('statements.account', $this->buildAccountStatementData($account, $request));
    }

    public function downloadAccountPdf(Request $request, Account $account)
    {
        abort_unless(auth()->user()->can('view', $account), 403);

        $pdf = Pdf::loadView('statements.account', $this->buildAccountStatementData($account, $request))
            ->setPaper('a4');

        return $pdf->download("releve-{$account->code}.pdf");
    }

    public function printCustomer(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->can('view', $customer), 403);

        return view('statements.customer', $this->buildCustomerStatementData($customer, $request));
    }

    public function downloadCustomerPdf(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->can('view', $customer), 403);

        $pdf = Pdf::loadView('statements.customer', $this->buildCustomerStatementData($customer, $request))
            ->setPaper('a4');

        return $pdf->download("releve-client-{$customer->code}.pdf");
    }

    /**
     * Periode normalisee : debut de journee / fin de journee. Indispensable
     * pour que la liste des transactions et le calcul du solde d'ouverture
     * portent exactement sur la meme fenetre.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))
            : now()->subMonth();

        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))
            : now();

        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function buildAccountStatementData(Account $account, Request $request): array
    {
        [$from, $to] = $this->resolvePeriod($request);

        $account->load(['typeOfAccount', 'currency', 'customer.person']);

        $transactions = $account->transactions()
            ->whereBetween('created_at', [$from, $to])
            ->with('currency')
            ->get();

        $result = $this->calculator->compute($account, $transactions, $from);

        $caseGrid = (bool) $account->typeOfAccount->active_case_payments
            ? $this->buildCaseGrid($account)
            : null;

        return [
            'account' => $account,
            'transactions' => $result['transactions'], // chronologiques, avec ->balance_after
            'openingBalance' => $result['opening'],
            'closingBalance' => $result['closing'],
            'caseGrid' => $caseGrid,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
        ];
    }

    private function buildCustomerStatementData(Customer $customer, Request $request): array
    {
        [$from, $to] = $this->resolvePeriod($request);

        $customer->load('person');

        $accounts = $customer->accounts()
            ->with(['typeOfAccount', 'currency'])
            ->get()
            ->map(function (Account $account) use ($from, $to) {
                $transactions = $account->transactions()
                    ->whereBetween('created_at', [$from, $to])
                    ->with('currency')
                    ->get();

                $result = $this->calculator->compute($account, $transactions, $from);

                $account->periodTransactions = $result['transactions'];
                $account->openingBalance = $result['opening'];
                $account->closingBalance = $result['closing'];

                return $account;
            });

        return [
            'customer' => $customer,
            'accounts' => $accounts,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
        ];
    }

    private function buildCaseGrid(Account $account): array
    {
        $duration = (int) $account->typeOfAccount->duration;
        $price = (float) $account->typeOfAccount->price;

        $paidTags = $account->tagsPayments()
            ->with('transaction')
            ->get()
            ->keyBy('tags');

        $months = [];

        for ($month = 1; $month <= $duration; $month++) {
            $casesInMonth = [];

            for ($n = ($month - 1) * 30 + 1; $n <= $month * 30; $n++) {
                $payment = $paidTags->get($n);

                $casesInMonth[] = [
                    'number' => $n,
                    'amount' => $n * $price,
                    'is_paid' => $payment !== null,
                    'paid_at' => $payment?->transaction?->created_at,
                ];
            }

            $months[$month] = $casesInMonth;
        }

        return [
            'months' => $months,
            'total_cases' => $duration * 30,
            'paid_count' => $paidTags->count(),
        ];
    }
}