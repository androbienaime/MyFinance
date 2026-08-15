<?php

namespace App\Actions;

use App\Exceptions\TransactionRejectedException;
use App\Models\Core\Customer;
use App\Services\CustomerLoginAuditService;
use Illuminate\Support\Facades\Hash;

class AuthenticateCustomerAction
{
    public function __construct(private CustomerLoginAuditService $auditService) {}

    public function handle(string $identifier, string $password, ?string $deviceName = null): array
    {
        if ($this->auditService->isLocked($identifier)) {
            $until = $this->auditService->lockedUntil($identifier);
            $this->auditService->record($identifier, 'blocked');

            throw new TransactionRejectedException(
                "Compte temporairement bloque. Reessayez apres " . $until->diffForHumans() . "."
            );
        }

        $customer = Customer::where('email', $identifier)
            ->orWhere('phone_number', $identifier)
            ->first();

        if (! $customer || ! Hash::check($password, $customer->password ?? '')) {
            $this->auditService->record($identifier, 'failed_password', $customer);
            throw new TransactionRejectedException('Identifiants incorrects.');
        }

        if (! $customer->isActivated()) {
            $this->auditService->record($identifier, 'account_inactive', $customer);
            throw new TransactionRejectedException('Ce compte n\'est pas encore active.');
        }

        if (! $customer->is_active) {
            $this->auditService->record($identifier, 'account_inactive', $customer);
            throw new TransactionRejectedException('Ce compte a ete desactive. Contactez votre agence.');
        }

        $this->auditService->record($identifier, 'success', $customer);

        // Un seul appareil actif a la fois : revoque tous les tokens
        // precedents avant d'en emettre un nouveau - protege contre le
        // vol de session sur un appareil perdu/vole non signale.
        $customer->tokens()->delete();

        $token = $customer->createToken(
            $deviceName ?? 'mobile-app',
            ['customer:access'],
            now()->addDays(30),
        );

        return [
            'customer' => $customer,
            'token' => $token->plainTextToken,
            'expires_at' => now()->addDays(30),
        ];
    }
}