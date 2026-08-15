<?php

namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\MerchantApiKey;
use App\Models\Core\MerchantProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateMerchantApiKeyAction
{
    // Ajustable : plafond de securite pour eviter l'accumulation
    // incontrolee de cles actives par marchand.
    private const MAX_ACTIVE_KEYS = 10;

    /**
     * @return array{key: MerchantApiKey, plain_text_key: string} La cle en
     * clair n'est jamais stockee ni recuperable ensuite - c'est la seule
     * fois qu'elle apparait dans la reponse.
     */
    public function handle(MerchantProfile $merchant, string $name): array
    {
        if ($merchant->activeApiKeysCount() >= self::MAX_ACTIVE_KEYS) {
            throw new TransactionRejectedException(
                'Limite de ' . self::MAX_ACTIVE_KEYS . ' cles actives atteinte. Revoquez une cle existante avant d\'en creer une nouvelle.'
            );
        }

        $prefix = 'mf_live_' . Str::random(12);
        $secret = Str::random(40);
        $plainTextKey = "{$prefix}.{$secret}";

        $key = MerchantApiKey::create([
            'merchant_profile_id' => $merchant->id,
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => Hash::make($secret),
            'is_active' => true,
        ]);

        return ['key' => $key, 'plain_text_key' => $plainTextKey];
    }
}