<?php

// app/Actions/ConfirmCustomerPasswordResetAction.php
namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Customer;
use App\Models\Core\CustomerPasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ConfirmCustomerPasswordResetAction
{
    private const MAX_ATTEMPTS = 3;

    public function handle(string $identifier, string $code, string $newPassword): void
    {
        $customer = Customer::where('email', $identifier)->orWhere('phone', $identifier)->firstOrFail();

        $reset = CustomerPasswordReset::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->latest()
            ->firstOrFail();

        if (now()->gt($reset->expires_at)) {
            $reset->update(['status' => 'expired']);
            throw new TransactionRejectedException('Ce code a expire.');
        }

        if ($reset->attempts >= self::MAX_ATTEMPTS) {
            $reset->update(['status' => 'failed']);
            throw new TransactionRejectedException('Trop de tentatives incorrectes.');
        }

        if (! Hash::check($code, $reset->otp_code_hash)) {
            $reset->increment('attempts');
            throw new TransactionRejectedException('Code incorrect.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['password' => $newPassword],
            ['password' => Password::min(10)->mixedCase()->numbers()]
        );

        if ($validator->fails()) {
            throw new TransactionRejectedException('Mot de passe trop faible.');
        }

        $customer->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
        ]);

        $reset->update(['status' => 'confirmed']);

        // Deconnexion de tous les appareils apres reinitialisation.
        $customer->tokens()->delete();
    }
}