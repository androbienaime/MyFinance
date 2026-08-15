<?php

namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Customer;
use App\Models\Core\CustomerPasswordReset;
use App\Notifications\CustomerPasswordResetOtp;
use Illuminate\Support\Facades\Hash;

class RequestCustomerPasswordResetAction
{
    public function handle(string $identifier): void
    {
        $customer = Customer::where('email', $identifier)->orWhere('phone', $identifier)->first();

        // Ne jamais reveler si l'identifiant existe ou non - reponse
        // identique dans les deux cas, cote controleur.
        if (! $customer || ! $customer->isActivated()) {
            return;
        }

        CustomerPasswordReset::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $code = (string) random_int(100000, 999999);

        CustomerPasswordReset::create([
            'customer_id' => $customer->id,
            'otp_code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'status' => 'pending',
        ]);

        $customer->notify(new CustomerPasswordResetOtp($code));
    }
}