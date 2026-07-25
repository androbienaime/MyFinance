<?php

namespace App\Services;

use App\Models\Core\Account;
use App\Notifications\TransferCredited;
use App\Notifications\TransferDebited;

class TransferNotifier
{
    public function notifyTransferCompleted(Account $from, Account $to, float $amount, float $feeAmount = 0.0): void
    {
        $from->customer?->notify(new TransferDebited($from, $to, $amount, $feeAmount));
        $to->customer?->notify(new TransferCredited($from, $to, $amount));
    }
}