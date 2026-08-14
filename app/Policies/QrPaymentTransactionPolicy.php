<?php

namespace App\Policies;

use App\Models\Core\Transaction;
use App\Models\User;

class QrPaymentTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('qr_payment_transactions.view_any');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->can('qr_payment_transactions.view');
    }
}