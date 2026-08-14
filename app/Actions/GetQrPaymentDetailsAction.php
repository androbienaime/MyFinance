<?php

namespace App\Actions;

use App\Enums\QrPaymentStatus;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\Customer;
use App\Models\Core\QrPaymentRequest;

class GetQrPaymentDetailsAction
{
    public function handle(string $reference, ?Customer $customer = null, ?string $accountCode = null): array
    {
        $paymentRequest = QrPaymentRequest::where('reference', $reference)
            ->with(['merchantProfile', 'currency'])
            ->first();

        if (! $paymentRequest) {
            throw new TransactionRejectedException('Ce code QR est invalide ou introuvable.');
        }

        if ($paymentRequest->status === QrPaymentStatus::Pending && $paymentRequest->isExpired()) {
            $paymentRequest->update(['status' => QrPaymentStatus::Expired]);
        }

        $converted = null;

        if ($accountCode && $customer) {
            $account = Account::where('code', $accountCode)->with('currency')->first();

            if ($account && $account->customer_id === $customer->id) {
                $converted = $this->buildConvertedAmounts($paymentRequest, $account);
            }
        }

        return ['payment' => $paymentRequest, 'converted' => $converted];
    }

    private function buildConvertedAmounts(QrPaymentRequest $paymentRequest, Account $account): ?array
    {
        if ($account->currency_id === $paymentRequest->currency_id) {
            return null;
        }

        $merchantCurrency = $paymentRequest->currency;
        $accountCurrency = $account->currency;

        $amount = $merchantCurrency->convertTo((float) $paymentRequest->amount, $accountCurrency);
        $feeAmount = $merchantCurrency->convertTo((float) $paymentRequest->fee_amount, $accountCurrency);
        $totalAmount = $merchantCurrency->convertTo((float) $paymentRequest->total_amount, $accountCurrency);

        return [
            'currency' => [
                'code' => $accountCurrency->iso_code,
                'name' => $accountCurrency->name,
            ],
            'exchange_rate_applied' => (float) $paymentRequest->amount > 0
                ? round($amount / (float) $paymentRequest->amount, 6)
                : 1.0,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'total_amount' => $totalAmount,
        ];
    }
}