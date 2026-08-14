<?php

namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\MerchantApiKey;
use App\Models\Core\MerchantProfile;

class RevokeMerchantApiKeyAction
{
    public function handle(MerchantProfile $merchant, MerchantApiKey $key): void
    {
        if ($key->merchant_profile_id !== $merchant->id) {
            throw new TransactionRejectedException('Cette cle n\'appartient pas a ce marchand.');
        }

        $key->update([
            'is_active' => false,
            'revoked_at' => now(),
        ]);
    }
}