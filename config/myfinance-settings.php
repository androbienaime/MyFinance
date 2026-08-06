<?php

return [
    'security' => [
        'label' => 'Sécurité',
        'settings' => [
            'security.2fa_required_for_employees' => [
                'type' => 'boolean',
                'label' => '2FA obligatoire pour les employés',
                'default' => false,
                'scope' => 'global',
            ],
            'security.2fa_required_roles' => [
                'type' => 'multiselect',
                'label' => 'Rôles concernés par le 2FA obligatoire',
                'options_resolver' => 'roles', // <-- string, pas de closure
                'default' => [],
                'scope' => 'global',
                'visible_when' => 'security.2fa_required_for_employees',
            ],
            'security.max_login_attempts_soft' => [
            'type' => 'integer',
            'label' => 'Tentatives avant verrouillage court (1 min)',
            'default' => 5,
            'scope' => 'global',
            ],
            'security.max_login_attempts_hard' => [
                'type' => 'integer',
                'label' => 'Tentatives avant verrouillage long (15 min)',
                'default' => 10,
                'scope' => 'global',
            ],
            'security.max_login_attempts_critical' => [
                'type' => 'integer',
                'label' => 'Tentatives avant verrouillage manuel admin',
                'default' => 15,
                'scope' => 'global',
            ],
            'security.lockout_duration_soft_minutes' => [
                'type' => 'integer',
                'label' => 'Durée verrouillage court (minutes)',
                'default' => 1,
                'scope' => 'global',
            ],
            'security.lockout_duration_hard_minutes' => [
                'type' => 'integer',
                'label' => 'Durée verrouillage long (minutes)',
                'default' => 15,
                'scope' => 'global',
            ],
            'security.max_concurrent_sessions' => [
                'type' => 'integer',
                'label' => 'Sessions simultanées max',
                'default' => 1,
                'scope' => 'global',
            ],
            'security.trusted_device_days' => [
                'type' => 'integer',
                'label' => 'Durée de confiance appareil (jours)',
                'default' => 30,
                'scope' => 'global',
            ],
            'security.password_expiry_days' => [
                'type' => 'integer',
                'label' => 'Expiration du mot de passe (jours, 0 = jamais)',
                'default' => 90,
                'scope' => 'global',
            ],
        ],
    ],

    'financial' => [
        'label' => 'Paramètres financiers',
        'settings' => [
            'financial.daily_withdrawal_limit' => [
                'type' => 'decimal',
                'label' => 'Plafond retrait journalier',
                'default' => 50000,
                'scope' => 'branch', // override possible par branche
            ],
            'financial.transaction_approval_threshold' => [
                'type' => 'decimal',
                'label' => 'Seuil nécessitant validation manager',
                'default' => 10000,
                'scope' => 'branch',
            ],
            'financial.default_currency' => [
                'type' => 'select',
                'label' => 'Devise par défaut',
                'options' => ['HTG' => 'HTG', 'USD' => 'USD'],
                'default' => 'HTG',
                'scope' => 'branch',
            ],
            'transactions.transfer_enabled' => [
                'type' => 'boolean', 'label' => 'Transfert Enabled',
                'default' => true, 'scope' => 'global',
            ],
            'financial.fees_account_code' => [
                'type' => 'text',
                'label' => "Fees Account Code",
                'default' => 'FRAIS-001'
            ],
            'financial.fee_for_transfer_in_branch_enabled' => [
                'type' => 'boolean', 'label' => 'Fee For Transfert in branch Enabled',
                'default' => true, 'scope' => 'global',
            ],
            'financial.default_early_withdrawal_fee_percentage' => [
                'type' => 'decimal',
                'label' => 'Frais de retrait anticipe par defaut',
                'default' => 0,
                'scope' => 'global', // override possible par branche
            ],
        ],
    ],

    'accounts' => [
        'label' => 'Comptes',
        'settings' => [
            'accounts.creation_fee_amount' => [
                'type' => 'number',
                'default' => 0,
                'label' => 'Frais de creation de compte (montant fixe)',
            ],
            'accounts.creation_fee_account_code' => [
                'type' => 'text',
                'default' => null,
                'label' => 'Code du compte recevant les frais de creation (vide = utilise le compte de frais general)',
            ],
        ],
    ],

    'merchants' => [
        'settings' => [
            'merchants.default_transaction_fee_percentage' => [
                'type' => 'number',
                'default' => 0,
                'label' => 'Pourcentage de frais par defaut sur les paiements marchands',
            ],
            'merchants.qr_expiration_seconds' => [
                'type' => 'number',
                'default' => 120,
                'label' => 'Duree de validite d\'un QR de paiement (secondes)',
            ],
        ],
    ],

    'localization' => [
        'label' => 'Localisation',
        'settings' => [
            'localization.default_locale' => [
                'type' => 'select',
                'label' => 'Langue par défaut',
                'options' => ['fr' => 'Français', 'ht' => 'Kreyòl Ayisyen'],
                'default' => 'fr',
                'scope' => 'global',
            ],
        ],
    ],

    'notifications' => [
        'label' => 'Notifications',
        'settings' => [
            'notifications.email_enabled' => [
                'type' => 'boolean', 'label' => 'Notifications par email',
                'default' => true, 'scope' => 'global',
            ],
            'notifications.sms_enabled' => [
                'type' => 'boolean', 'label' => 'Notifications par SMS',
                'default' => false, 'scope' => 'global',
            ],
        ],
    ],
];
