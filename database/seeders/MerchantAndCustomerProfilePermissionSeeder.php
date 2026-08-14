<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\PreparesPermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MerchantAndCustomerProfilePermissionSeeder extends Seeder
{
    use PreparesPermissions;

    protected array $permissions = [
        // Cles API - supervision admin, jamais de creation manuelle
        'merchant_api_keys.view_any' => 50,
        'merchant_api_keys.view' => 50,
        'merchant_api_keys.revoke' => 60,

        // Audit des connexions au tableau de bord marchand
        'merchant_login_attempts.view_any' => 80,
        'merchant_login_attempts.view' => 80,

        // Audit de connexions des clients - supervision admin, jamais de creation manuelle
        'customer_login_attempts.view_any' => 80,
        'customer_login_attempts.view' => 80,

        // Audit de blocages de comptes clients - supervision admin, jamais de creation manuelle
        'customer_account_lockouts.view_any' => 80,
        'customer_account_lockouts.view' => 80,

        'merchant_account_lockouts.view' => 80,
        'merchant_account_lockouts.view_any' => 80,

        'merchant_idempotency_keys.view_any' => 80,
        'merchant_idempotency_keys.view' => 80,

        'customer_password_resets.view_any' => 80,
        'customer_password_resets.view' => 80,

        'customers.enable_online_access' => 20,

        'qr_payment_requests.view_any' => 40,
        'qr_payment_requests.view' => 40,
        'qr_payment_requests.cancel' => 40,

        'qr_payment_fee_tiers.view_any' => 60,
        'qr_payment_fee_tiers.view' => 60,
        'qr_payment_fee_tiers.create' => 70,
        'qr_payment_fee_tiers.update' => 70,
        'qr_payment_fee_tiers.delete' => 80,
        'qr_payment_transactions.view_any' => 40,
        'qr_payment_transactions.view' => 40,

    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->preparePermissions($this->permissions);
    }
}
