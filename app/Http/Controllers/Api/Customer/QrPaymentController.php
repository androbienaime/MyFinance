<?php

namespace App\Http\Controllers\Api\Customer;

use App\Actions\GetQrPaymentDetailsAction;
use App\Actions\ProcessQrPaymentAction;
use App\Exceptions\TransactionRejectedException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QrPaymentController extends Controller
{
    public function show(Request $request, string $reference, GetQrPaymentDetailsAction $action)
    {
        $accountCode = $request->query('account_code');
        $customer = $request->user('customer');

        try {
            $result = $action->handle($reference, $customer, $accountCode);
            $payment = $result['payment'];

            return response()->json([
                'reference' => $payment->reference,
                'status' => $payment->status->value,
                'description' => $payment->description,
                'merchant' => [
                    'business_name' => $payment->merchantProfile->business_name,
                    'category' => $payment->merchantProfile->category,
                ],
                'original' => [
                    'currency' => [
                        'code' => $payment->currency->iso_code,
                        'name' => $payment->currency->name,
                    ],
                    'amount' => (float) $payment->amount,
                    'fee_amount' => (float) $payment->fee_amount,
                    'total_amount' => (float) $payment->total_amount,
                ],
                // Present uniquement si account_code fourni ET compte
                // dans une devise differente de celle du marchand.
                'converted' => $result['converted'],
                'expires_at' => $payment->expires_at,
            ]);
        } catch (TransactionRejectedException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function pay(Request $request, string $reference, ProcessQrPaymentAction $action)
    {
        $data = $request->validate([
            'account_code' => ['required', 'string'],
            'pin' => ['nullable', 'string'],
        ]);

        $customer = $request->user('customer');

        try {
            $payment = $action->handle($reference, $customer, $data['account_code'], $data['pin'] ?? null);

            return response()->json([
                'status' => $payment->status->value,
                'total_amount' => (float) $payment->total_amount,
                'currency' => [
                    'code' => $payment->currency->iso_code,
                ],
                'paid_at' => $payment->paid_at,
            ]);
        } catch (TransactionRejectedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}