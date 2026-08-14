<?php

namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\MerchantApiKey;
use App\Models\Core\MerchantProfile;
use App\Models\Core\QrPaymentRequest;
use App\Services\QrPaymentFeeResolver;
use Illuminate\Support\Str;

class GenerateQrPaymentRequestAction
{
    public function __construct(private QrPaymentFeeResolver $feeResolver) {}

    public function handle(MerchantProfile $merchant, float $amount, ?string $description = null, ?MerchantApiKey $apiKey = null): QrPaymentRequest
    {
        if ($amount <= 0) {
            throw new TransactionRejectedException('Le montant doit etre superieur a 0.');
        }

        $merchantCurrency = $merchant->account->currency;

        $feePercentage = $this->feeResolver->resolve($merchant, $amount, $merchantCurrency);
        $feeAmount = round($amount * $feePercentage / 100, 2);
        $totalAmount = round($amount + $feeAmount, 2);

        return QrPaymentRequest::create([
            'reference' => (string) Str::uuid(),
            'merchant_profile_id' => $merchant->id,
            'merchant_api_key_id' => $apiKey?->id,
            'currency_id' => $merchantCurrency->id,
            'description' => $description,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'total_amount' => $totalAmount,
            'status' => \App\Enums\QrPaymentStatus::Pending,
            'expires_at' => now()->addSeconds((int) setting('merchants.qr_expiration_seconds', 120)),
        ]);
    }
}