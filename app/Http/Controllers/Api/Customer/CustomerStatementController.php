<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Enums\TransactionType;
use App\Models\Core\Account;
use App\Services\StatementBalanceCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CustomerStatementController extends Controller
{
    public function __construct(private StatementBalanceCalculator $calculator) {}

    /**
     * Relevé d'un compte du client connecté, sur une période :
     * ?period=30d (défaut) | 3m | 6m | year
     * La période est calculée avec l'heure du SERVEUR (pas celle du téléphone).
     */
    public function show(Request $request, string $accountCode): JsonResponse
    {
        $account = $this->ownedAccount($request, $accountCode);
        [$from, $to] = $this->resolvePeriod($request);

        $transactions = $account->transactions()
            ->whereBetween('created_at', [$from, $to])
            ->with(['currency', 'qrPaymentRequest.merchantProfile'])
            ->get();

        $result = $this->calculator->compute($account, $transactions, $from);

        $previous = (float) $result['opening'];
        $lines = collect($result['transactions'])->map(function ($t) use (&$previous) {
            $after = (float) $t->balance_after;
            $line = [
                'code' => $t->code,
                'type_label' => $t->type->label(),
                'merchant_name' => $t->type === TransactionType::QrPayment
                    ? $t->qrPaymentRequest?->merchantProfile?->business_name
                    : null,
                'amount' => (float) $t->amount,
                'is_debit' => $after < $previous,   // sens déduit de l'évolution du solde
                'balance_after' => $after,
                'created_at' => $t->created_at,
            ];
            $previous = $after;

            return $line;
        })->values();

        return response()->json([
            'account' => [
                'code' => $account->code,
                'name' => $account->typeOfAccount->name,
                'currency' => ['code' => $account->currency?->iso_code],
            ],
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'opening_balance' => (float) $result['opening'],
            'closing_balance' => (float) $result['closing'],
            'transactions' => $lines,
        ]);
    }

    /**
     * Lien temporaire (5 min) vers le relevé PDF : l'application l'ouvre dans le navigateur du téléphone.
     * Le lien est signé : aucune session ni jeton n'est nécessaire pour le télécharger.
     */
    public function link(Request $request, string $accountCode): JsonResponse
    {
        $account = $this->ownedAccount($request, $accountCode);
        [$from, $to] = $this->resolvePeriod($request);

        $url = URL::temporarySignedRoute('statements.mobile.download', now()->addMinutes(5), [
            'code' => $account->code,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);

        return response()->json(['url' => $url]);
    }

    private function ownedAccount(Request $request, string $accountCode): Account
    {
        return $request->user('customer')->accounts()
            ->where('code', $accountCode)
            ->with(['typeOfAccount', 'currency'])
            ->firstOrFail();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolvePeriod(Request $request): array
    {
        $from = match ($request->query('period')) {
            '3m' => now()->subMonths(3),
            '6m' => now()->subMonths(6),
            'year' => now()->startOfYear(),
            default => now()->subDays(30),
        };

        return [$from->startOfDay(), now()->endOfDay()];
    }
}