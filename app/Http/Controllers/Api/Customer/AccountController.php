<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Liste des comptes du client connecte, avec leur type de compte.
     * Ordre : comptes actifs d'abord, puis les plus recemment crees
     * en premier au sein de chaque groupe.
     */
    public function index(Request $request)
    {
        $customer = $request->user('customer');

        $accounts = $customer->accounts()
            ->with(['typeOfAccount', 'currency'])
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(
            $accounts->map(fn ($account) => [
                'code' => $account->code,
                'holder_type' => $account->holder_type?->value,
                'balance' => (float) $account->balance,
                'available_balance' => $account->availableBalance(),
                'is_active' => $account->is_active,
                'created_at' => $account->created_at,
                'currency' => [
                    'code' => $account->currency?->iso_code,
                    'name' => $account->currency?->name,
                ],
                'type_of_account' => [
                    'id' => $account->typeOfAccount->id,
                    'name' => $account->typeOfAccount->name,
                    'active_case_payments' => (bool) $account->typeOfAccount->active_case_payments,
                    'duration' => $account->typeOfAccount->duration,
                    'price' => $account->typeOfAccount->active_case_payments
                        ? (float) $account->typeOfAccount->price
                        : null,
                ],
            ])
        );
    }

    /**
     * Historique des transactions d'un compte precis appartenant au
     * client connecte. Les plus recentes en premier, pagine.
     */
    public function transactions(Request $request, string $accountCode)
    {
        $customer = $request->user('customer');

        $account = $customer->accounts()->where('code', $accountCode)->firstOrFail();

        $transactions = $account->transactions()
            ->with('counterpartyAccount')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $transactions->getCollection()->map(fn ($t) => [
                'code' => $t->code,
                'type' => $t->type->value,
                'type_label' => $t->type->label(),
                'direction' => $t->direction?->value,
                'amount' => (float) $t->amount,
                'status' => $t->status->value,
                'counterparty_account_code' => $t->counterpartyAccount?->code,
                'initiated_by' => $t->employee_id ? 'employee' : ($t->initiated_by_customer_id ? 'customer' : null),
                'created_at' => $t->created_at,
            ]),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}