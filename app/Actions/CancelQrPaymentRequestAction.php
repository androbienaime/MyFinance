<?php

namespace App\Actions;

use App\Enums\QrPaymentStatus;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\MerchantProfile;
use App\Models\Core\QrPaymentRequest;

class CancelQrPaymentRequestAction
{
    public function handle(MerchantProfile $merchant, string $reference): QrPaymentRequest
    {
        $paymentRequest = QrPaymentRequest::where('reference', $reference)
            ->where('merchant_profile_id', $merchant->id)
            ->lockForUpdate()
            ->first();

        if (! $paymentRequest) {
            throw new TransactionRejectedException('Paiement introuvable.');
        }

        if ($paymentRequest->status !== QrPaymentStatus::Pending) {
            throw new TransactionRejectedException('Ce paiement ne peut plus etre annule.');
        }

        $paymentRequest->update(['status' => QrPaymentStatus::Cancelled]);

        return $paymentRequest;
    }
}