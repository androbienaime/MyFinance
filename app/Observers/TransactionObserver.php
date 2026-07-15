<?php

// app/Observers/TransactionObserver.php

namespace App\Observers;

use App\Enums\TransactionStatus;
use App\Models\Core\Transaction;
use App\Notifications\TransactionConfirmed;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        if ($transaction->status === TransactionStatus::Completed) {
            $transaction->account->customer->notify(
                new TransactionConfirmed($transaction)
            );
        }
    }
}