<?php

// app/Actions/ConfirmP2pTransferAction.php
namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Customer;
use App\Models\Core\P2pTransferRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConfirmP2pTransferAction
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(private TransferAction $transferAction) {}

    public function handle(Customer $customer, int $requestId, string $code): array
    {
        return DB::transaction(function () use ($customer, $requestId, $code) {
            $request = P2pTransferRequest::where('customer_id', $customer->id)
                ->where('id', $requestId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->isExpired()) {
                $request->update(['status' => 'expired']);
                throw new TransactionRejectedException('Ce code a expire. Recommencez le virement.');
            }

            if ($request->attempts >= self::MAX_ATTEMPTS) {
                $request->update(['status' => 'failed']);
                throw new TransactionRejectedException('Trop de tentatives incorrectes. Recommencez le virement.');
            }

            if (! Hash::check($code, $request->otp_code_hash)) {
                $request->increment('attempts');
                throw new TransactionRejectedException('Code incorrect.');
            }

            $request->update(['status' => 'confirmed', 'confirmed_at' => now()]);

            return $this->transferAction->handle(
                fromAccountCode: $request->fromAccount->code,
                toAccountCode: $request->toAccount->code,
                amount: (float) $request->amount,
                initiatingCustomer: $customer,
                feeAmount: (float) $request->fee_amount,
            );
        });
    }
}