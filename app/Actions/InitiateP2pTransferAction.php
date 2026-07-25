<?php

// app/Actions/InitiateP2pTransferAction.php
namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Models\Core\Customer;
use App\Models\Core\P2pTransferFeeTier;
use App\Models\Core\P2pTransferLimit;
use App\Models\Core\P2pTransferRequest;
use App\Notifications\P2pTransferOtpCode;
use Illuminate\Support\Facades\Hash;

class InitiateP2pTransferAction
{
    public function handle(Customer $customer, string $fromAccountCode, string $toAccountCode, float $amount): P2pTransferRequest
    {
        if ($amount <= 0) {
            throw new TransactionRejectedException('Le montant doit etre superieur a 0.');
        }

        $from = Account::where('code', $fromAccountCode)->with('typeOfAccount')->firstOrFail();
        $to = Account::where('code', $toAccountCode)->with('typeOfAccount')->firstOrFail();

        if (! $from->customer || $from->customer->id !== $customer->id) {
            throw new TransactionRejectedException('Ce compte ne vous appartient pas.');
        }

        if ($from->id === $to->id) {
            throw new TransactionRejectedException('Le compte source et destinataire ne peuvent pas etre identiques.');
        }

        if (! $from->is_active || ! $to->is_active) {
            throw new TransactionRejectedException('Un des deux comptes est desactive.');
        }

        if ((bool) $from->typeOfAccount->active_case_payments || (bool) $to->typeOfAccount->active_case_payments) {
            throw new TransactionRejectedException('Ce type de compte ne peut pas participer a un virement P2P.');
        }

        $fee = P2pTransferFeeTier::feeFor($amount);
        $totalDebit = $amount + $fee;

        $this->assertWithinLimits($customer, $amount);

        if ($totalDebit > $from->availableBalance()) {
            throw new TransactionRejectedException('Solde disponible insuffisant (frais inclus).');
        }

        P2pTransferRequest::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $otpCode = (string) random_int(100000, 999999);

        $request = P2pTransferRequest::create([
            'customer_id' => $customer->id,
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => $amount,
            'fee_amount' => $fee,
            'otp_code_hash' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(5),
            'status' => 'pending',
        ]);

        $customer->notify(new P2pTransferOtpCode($otpCode, $amount, $fee, $to->code));

        return $request;
    }

    private function assertWithinLimits(Customer $customer, float $amount): void
    {
        $limits = P2pTransferLimit::current();

        if ($amount > $limits->max_per_transaction) {
            throw new TransactionRejectedException(
                "Montant superieur a la limite par transaction ({$limits->max_per_transaction} HTG)."
            );
        }

        $confirmedToday = P2pTransferRequest::where('customer_id', $customer->id)
            ->where('status', 'confirmed')
            ->whereDate('confirmed_at', today());

        if ($confirmedToday->count() >= $limits->max_daily_count) {
            throw new TransactionRejectedException('Nombre maximum de virements quotidiens atteint.');
        }

        if (($confirmedToday->sum('amount') + $amount) > $limits->max_daily_amount) {
            throw new TransactionRejectedException('Limite de montant quotidien atteinte.');
        }

        $monthlyTotal = P2pTransferRequest::where('customer_id', $customer->id)
            ->where('status', 'confirmed')
            ->whereMonth('confirmed_at', now()->month)
            ->whereYear('confirmed_at', now()->year)
            ->sum('amount');

        if (($monthlyTotal + $amount) > $limits->max_monthly_amount) {
            throw new TransactionRejectedException('Limite de montant mensuel atteinte.');
        }
    }
}