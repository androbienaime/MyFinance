<?php

// app/Observers/TransactionObserver.php

namespace App\Observers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Core\Transaction;
use App\Notifications\TransactionConfirmed;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        // Les virements (jambes principales + frais) gerent leurs propres
        // notifications via TransferNotifier - eviter un doublon WhatsApp.
        if (in_array($transaction->type, [TransactionType::Transfer, TransactionType::TransferFee], true)) {
            return;
        }

        if ($transaction->status === TransactionStatus::Completed) {
            $transaction->account->customer?->notify(new TransactionConfirmed($transaction));
        }
    }
}