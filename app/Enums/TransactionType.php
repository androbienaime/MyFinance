<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case AccountSettlement  = 'accountSettlement';
    case AccountClosure = 'accountClosure';
    case AccountRestoration = 'AccountRestoration';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Depot',
            self::Withdrawal => 'Retrait',
            self::AccountSettlement  => 'Account Settlement',
            self::AccountClosure => 'Account Closure',
            self::AccountRestoration => 'Account Restoration',
        };
    }
}