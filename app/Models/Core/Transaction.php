<?php

namespace App\Models\Core;

use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'code',
        'amount',
        'employee_id',
        'initiated_by_customer_id',
        'type',
        'status',
        'transfer_group_id',
        'direction',
        'counterparty_account_id',
        'deleted_by',
        'deletion_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'direction' => TransactionDirection::class,
    ];

    public function approvals(): HasMany
    {
        return $this->hasMany(TransactionApproval::class);
    }
    
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function initiatedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'initiated_by_customer_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function counterpartyAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'counterparty_account_id');
    }

    public function tagsPayments(): HasMany
    {
        return $this->hasMany(TagsPayment::class);
    }
   

    public static function generateUniqueCode(int $attempts = 5): string
    {
        for ($i = 0; $i < $attempts; $i++) {
            $candidate = (string) random_int(10000000000, 99999999999);

            if (! static::withTrashed()->where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Impossible de generer un code de transaction unique apres plusieurs tentatives.');
    }

    public function scopeOrderByRelevanceTo(Builder $query, int $currentBranchId, int $currentEmployeeId): Builder
    {
        return $query
            ->join('employees', 'employees.id', '=', 'transactions.employee_id')
            ->selectRaw('transactions.*')
            ->orderByRaw('CASE WHEN transactions.employee_id = ? THEN 0 ELSE 1 END', [$currentEmployeeId])
            ->orderByRaw('CASE WHEN employees.branch_id = ? THEN 0 ELSE 1 END', [$currentBranchId])
            ->orderByDesc('transactions.created_at');
    }

    public function scopeDepositsAndWithdrawalsByDay(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->selectRaw("DATE(transactions.created_at) as transaction_date,
                SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as deposit_sum,
                SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as withdrawal_sum,
                SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as payment_sum", [
                TransactionType::Deposit->value,
                TransactionType::Withdrawal->value,
                TransactionType::AccountSettlement->value,
            ])
            ->groupBy('transaction_date')
            ->orderByDesc('transaction_date');
    }

    /**
     * Regroupe une liste de nombres (tags) en intervalles consecutifs.
     * Exemple : [1,2,3,4,5,6,7] -> ['1-7']
     *           [16,15,14,13]   -> ['13-16']
     *           [1,2,5,6,9]     -> ['1-2', '5-6', '9']
     */
    public static function compressTagsToRanges($tags): array
    {
        if (blank($tags)) {
            return [];
        }

        // Accepte un tableau brut ou une collection/JSON deja decode.
        $numbers = collect($tags)
            ->map(fn ($n) => (int) $n)
            ->unique()
            ->sort()
            ->values();

        if ($numbers->isEmpty()) {
            return [];
        }

        $ranges = [];
        $start = $numbers->first();
        $prev = $start;

        foreach ($numbers->slice(1) as $n) {
            if ($n === $prev + 1) {
                // toujours dans la meme suite consecutive
                $prev = $n;
                continue;
            }

            // rupture de la suite : on cloture l'intervalle en cours
            $ranges[] = $start === $prev ? (string) $start : "{$start}-{$prev}";
            $start = $n;
            $prev = $n;
        }

        // dernier intervalle
        $ranges[] = $start === $prev ? (string) $start : "{$start}-{$prev}";

        return $ranges;
    }
}
