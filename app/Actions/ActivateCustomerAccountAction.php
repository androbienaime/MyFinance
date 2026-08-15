<?php

namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ActivateCustomerAccountAction
{
    public function handle(Customer $customer, string $activationCode, string $newPassword): Customer
    {
        if ($customer->isActivated()) {
            throw new TransactionRejectedException('Ce compte est deja active.');
        }

        if (! $customer->activation_expires_at || now()->gt($customer->activation_expires_at)) {
            throw new TransactionRejectedException('Le code d\'activation a expire. Contactez votre agence.');
        }

        if (! $customer->activation_code_hash || ! Hash::check($activationCode, $customer->activation_code_hash)) {
            throw new TransactionRejectedException('Code d\'activation incorrect.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['password' => $newPassword],
            ['password' => Password::min(10)->mixedCase()->numbers()]
        );

        if ($validator->fails()) {
            throw new TransactionRejectedException('Mot de passe trop faible : minimum 10 caracteres, majuscules/minuscules et chiffres.');
        }

        $customer->update([
            'password' => Hash::make($newPassword),
            'activation_code_hash' => null,
            'activation_expires_at' => null,
            'activated_at' => now(),
            'password_changed_at' => now(),
            'is_online' => true,
        ]);

        return $customer;
    }
}