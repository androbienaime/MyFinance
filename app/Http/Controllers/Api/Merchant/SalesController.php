<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $merchant = $request->user('merchant');
        $account = $merchant->account;

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
                'created_at' => $t->created_at,
            ]),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function summary(Request $request)
    {
        $merchant = $request->user('merchant');
        $account = $merchant->account;

        $today = $account->transactions()
            ->whereDate('created_at', today())
            ->where('status', TransactionStatus::Completed->value)
            ->sum('amount');

        $thisMonth = $account->transactions()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', TransactionStatus::Completed->value)
            ->sum('amount');

        return response()->json([
            'today_total' => (float) $today,
            'month_total' => (float) $thisMonth,
            'current_balance' => (float) $account->balance,
        ]);
    }
}