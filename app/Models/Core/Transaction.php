<?php

namespace App\Models\Core;

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
        'type',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
    ];

    public function approvals(): HasMany
    {
        return $this->hasMany(TransactionApproval::class);
    }
    
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function tagsPayments(): HasMany
    {
        return $this->hasMany(TagsPayment::class);
    }
   

    public static function generateUniqueCode(int $attempts = 5): string
    {
        for ($i = 0; $i < $attempts; $i++) {
            $candidate = (string) random_int(100000000, 999999999);

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
}
