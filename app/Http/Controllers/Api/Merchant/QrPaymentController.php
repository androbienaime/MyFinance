<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\CancelQrPaymentRequestAction;
use App\Actions\GenerateQrPaymentRequestAction;
use App\Exceptions\TransactionRejectedException;
use App\Http\Controllers\Controller;
use App\Models\Core\QrPaymentRequest;
use Illuminate\Http\Request;

class QrPaymentController extends Controller
{
    public function store(Request $request, GenerateQrPaymentRequestAction $action)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $merchant = $request->attributes->get('merchant');
        $apiKey = $request->attributes->get('merchant_api_key');

        try {
            $payment = $action->handle($merchant, (float) $data['amount'], $data['description'] ?? null, $apiKey);

            return response()->json([
                'reference' => $payment->reference,
                'amount' => (float) $payment->amount,
                'total_amount' => (float) $payment->total_amount,
                'expires_at' => $payment->expires_at,
                // Contenu a encoder dans le QR - reference uniquement,
                // jamais le montant (voir principe de securite en tete).
                'qr_payload' => json_encode(['ref' => $payment->reference]),
            ], 201);
        } catch (TransactionRejectedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, string $reference)
    {
        $merchant = $request->attributes->get('merchant');

        $payment = QrPaymentRequest::where('reference', $reference)
            ->where('merchant_profile_id', $merchant->id)
            ->firstOrFail();

        return response()->json([
            'reference' => $payment->reference,
            'status' => $payment->status->value,
            'amount' => (float) $payment->amount,
            'total_amount' => (float) $payment->total_amount,
            'paid_at' => $payment->paid_at,
        ]);
    }

    public function cancel(Request $request, string $reference, CancelQrPaymentRequestAction $action)
    {
        $merchant = $request->attributes->get('merchant');

        try {
            $action->handle($merchant, $reference);

            return response()->json(['message' => 'Paiement annule.']);
        } catch (TransactionRejectedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}