<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case AccountSettlement  = 'accountSettlement';
    case AccountClosure = 'accountClosure';
    case AccountRestoration = 'AccountRestoration';
    case Transfer = 'transfer';
    case TransferFee = 'transfer_fee';
    case SettlementFee = 'settlement_fee';
    case AccountCreationFee = 'account_creation_fee';
    case QrPayment = 'qr_payment';
    case QrPaymentFee = 'qr_payment_fee';



    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Depot',
            self::Withdrawal => 'Retrait',
            self::AccountSettlement  => 'Account Settlement',
            self::AccountClosure => 'Account Closure',
            self::AccountRestoration => 'Account Restoration',
            self::Transfer => 'Transfert',
            self::TransferFee => 'Frais de virement',
            self::SettlementFee => 'Frais de retrait anticipe',
            self::AccountCreationFee => 'Frais de creation de compte',
            self::QrPayment => 'Paiement QR',
            self::QrPaymentFee => 'Frais de paiement QR',
        };
    }
}