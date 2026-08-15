<?php

namespace App\Http\Controllers;

use App\Models\Core\Account;
use App\Models\Core\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StatementController extends Controller
{
    public function printAccount(Request $request, Account $account)
    {
        abort_unless(auth()->user()->can('view', $account), 403);

        $data = $this->buildAccountStatementData($account, $request);

        return view('statements.account', $data);
    }

    public function downloadAccountPdf(Request $request, Account $account)
    {
        abort_unless(auth()->user()->can('view', $account), 403);

        $data = $this->buildAccountStatementData($account, $request);

        $pdf = Pdf::loadView('statements.account', $data)->setPaper('a4');

        return $pdf->download("releve-{$account->code}.pdf");
    }

    public function printCustomer(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->can('view', $customer), 403);

        $data = $this->buildCustomerStatementData($customer, $request);

        return view('statements.customer', $data);
    }

    public function downloadCustomerPdf(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->can('view', $customer), 403);

        $data = $this->buildCustomerStatementData($customer, $request);

        $pdf = Pdf::loadView('statements.customer', $data)->setPaper('a4');

        return $pdf->download("releve-client-{$customer->code}.pdf");
    }

    private function buildAccountStatementData(Account $account, Request $request): array
    {
        $from = $request->query('from') ? \Carbon\Carbon::parse($request->query('from')) : now()->subMonths(1);
        $to = $request->query('to') ? \Carbon\Carbon::parse($request->query('to'))->endOfDay() : now();

        $account->load(['typeOfAccount', 'currency', 'customer.person']);

        $transactions = $account->transactions()
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $caseGrid = null;

        if ((bool) $account->typeOfAccount->active_case_payments) {
            $caseGrid = $this->buildCaseGrid($account);
        }

        return [
            'account' => $account,
            'transactions' => $transactions,
            'caseGrid' => $caseGrid,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
        ];
    }

    private function buildCustomerStatementData(Customer $customer, Request $request): array
    {
        $from = $request->query('from') ? \Carbon\Carbon::parse($request->query('from')) : now()->subMonths(1);
        $to = $request->query('to') ? \Carbon\Carbon::parse($request->query('to'))->endOfDay() : now();

        $customer->load('person');

        $accounts = $customer->accounts()
            ->with(['typeOfAccount', 'currency'])
            ->get()
            ->map(function ($account) use ($from, $to) {
                $account->periodTransactions = $account->transactions()
                    ->whereBetween('created_at', [$from, $to])
                    ->orderBy('created_at')
                    ->get();

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