<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Payment = 'payment';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Depot',
            self::Withdrawal => 'Retrait',
            self::Payment => 'Paiement',
        };
    }
}