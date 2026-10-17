<?php

// app/Services/StatementBalanceCalculator.php
namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Core\Account;
use App\Models\Core\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatementBalanceCalculator
{
    /**
     * @param  Collection<int, Transaction>  $transactions  transactions de la periode pour ce compte
     * @return array{opening: float, closing: float, transactions: Collection}
     */
    public function compute(Account $account, Collection $transactions, Carbon $from): array
    {
        // Effet net de toutes les transactions COMPLETEES depuis le debut
        // de la periode (y compris apres la fin de la periode) : retire du
        // solde actuel, il donne le solde d'ouverture.
        $netSinceFrom = (float) Transaction::query()
            ->where('account_id', $account->id)
            ->where('status', TransactionStatus::Completed)
            ->where('created_at', '>=', $from->copy()->startOfDay())
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' OR type = 'deposit' THEN amount ELSE -amount END), 0) as net")
            ->value('net');

        $opening = round((float) $account->balance - $netSinceFrom, 2);
        $running = $opening;

        $sorted = $transactions
            ->sortBy([['created_at', 'asc'], ['id', 'asc']])
            ->values();

        foreach ($sorted as $t) {
            if ($t->status === TransactionStatus::Completed) {
                $isCredit = $t->direction?->value === 'credit' || $t->type->value === 'deposit';
                $running = round($running + ($isCredit ? (float) $t->amount : -(float) $t->amount), 2);
                $t->balance_after = $running;
            } else {
                // En attente / rejetee : n'a pas (encore) touche le solde.
                $t->balance_after = null;
            }
        }

        return [
            'opening' => $opening,
            'closing' => $running,
            'transactions' => $sorted,
        ];
    }
}