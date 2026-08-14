<?php

namespace App\Actions;

use App\Enums\MerchantStatus;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Account;
use App\Services\MerchantLoginAuditService;
use Illuminate\Support\Facades\Hash;

class AuthenticateMerchantAction
{
    public function __construct(private MerchantLoginAuditService $auditService) {}

    public function handle(string $accountCode, string $password, ?string $deviceName = null): array
    {
        if ($this->auditService->isLocked($accountCode)) {
            $until = $this->auditService->lockedUntil($accountCode);
            $this->auditService->record($accountCode, 'blocked');

            throw new TransactionRejectedException(
                "Compte temporairement bloque. Reessayez apres " . $until->diffForHumans() . "."
            );
        }

        $account = Account::where('code', $accountCode)->with('merchantProfile')->first();
        $profile = $account?->merchantProfile;

        if (! $profile || ! Hash::check($password, $profile->api_password ?? '')) {
            $this->auditService->record($accountCode, 'failed_password', $profile);
            throw new TransactionRejectedException('Identifiants incorrects.');
        }

        if ($profile->status !== MerchantStatus::Active) {
            $this->auditService->record($accountCode, 'account_inactive', $profile);
            throw new TransactionRejectedException('Ce compte marchand n\'est pas actif.');
        }

        if (! $account->is_active) {
            $this->auditService->record($accountCode, 'account_inactive', $profile);
            throw new TransactionRejectedException('Ce compte a ete desactive.');
        }

        $this->auditService->record($accountCode, 'success', $profile);

        // Dashboard uniquement : plusieurs sessions simultanees tolerees
        // (gerant + comptable connectes en meme temps, par exemple).
        $token = $profile->createToken(
            $deviceName ?? 'merchant-dashboard',
            ['merchant:dashboard'],
            now()->addDays(30),
        );

        return [
            'merchant' => $profile,
            'token' => $token->plainTextToken,
            'expires_at' => now()->addDays(30),
        ];
    }
}