<?php

namespace App\Policies;

use App\Models\Core\MerchantApiKey;
use App\Models\User;

class MerchantApiKeyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('merchant_api_keys.view_any');
    }

    public function view(User $user, MerchantApiKey $key): bool
    {
        return $user->can('merchant_api_keys.view');
    }

    public function revoke(User $user, MerchantApiKey $key): bool
    {
        return $user->can('merchant_api_keys.revoke');
    }

    public function create(User $user): bool
    {
        return false; // uniquement genere par le marchand lui-meme via l'API
    }

    public function delete(User $user, MerchantApiKey $key): bool
    {
        return false; // on revoque, on ne supprime jamais l'enregistrement (audit)
    }
}