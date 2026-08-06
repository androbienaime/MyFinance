<?php

namespace App\Models\Core;

use App\Enums\CaisseSessionStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\CaisseSessionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CaisseSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'session_date',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected $casts = [
        'session_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'status' => CaisseSessionStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(CaisseSessionBalance::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', CaisseSessionStatus::Open->value);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', CaisseSessionStatus::Closed->value);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('session_date', $date);
    }

    /**
     * Ouvre la caisse (le shift) d'un caissier pour la journee, et cree une
     * ligne de solde pour chaque devise active du systeme. Le solde attendu
     * de chaque devise est celui laisse dans le tiroir a la derniere
     * fermeture de ce meme employe, pour cette meme devise (0 si jamais
     * utilisee auparavant).
     *
     * @param  array<int, float>  $declaredByCurrencyId  ex: [1 => 5000, 2 => 200]
     */
    public static function openFor(Employee $employee, array $declaredByCurrencyId, ?Carbon $date = null): self
    {
        $date ??= Carbon::today();

        if (static::where('employee_id', $employee->id)->forDate($date)->exists()) {
            throw new CaisseSessionException('Une session de caisse existe deja pour cet employe a cette date.');
        }

        $session = static::create([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'session_date' => $date->toDateString(),
            'opened_at' => now(),
            'status' => CaisseSessionStatus::Open,
        ]);

        foreach (Currency::all() as $currency) {
            $declared = (float) ($declaredByCurrencyId[$currency->id] ?? 0);

            $previousBalance = CaisseSessionBalance::query()
                ->where('currency_id', $currency->id)
                ->whereHas('caisseSession', fn ($q) => $q
                    ->where('employee_id', $employee->id)
                    ->closed())
                ->latest('id')
                ->first();

            $expected = (float) ($previousBalance?->closing_balance_declared ?? 0);

            $session->balances()->create([
                'currency_id' => $currency->id,
                'opening_balance_declared' => $declared,
                'opening_balance_expected' => $expected,
                'opening_discrepancy' => round($declared - $expected, 2),
            ]);
        }

        return $session;
    }

    /**
     * Ferme la caisse : pour chaque devise active, calcule les mouvements
     * reels de la journee depuis les transactions completees du caissier
     * (filtrees par la devise du compte concerne), en deduit le solde
     * attendu, et compare au comptage physique declare pour cette devise.
     *
     * @param  array<int, array{declared: float, comment?: string|null}>  $closingData  cle = currency_id
     */
    public function closeWith(array $closingData): void
    {
        if ($this->status === CaisseSessionStatus::Closed) {
            throw new CaisseSessionException('Cette session de caisse est deja fermee.');
        }

        foreach ($this->balances as $balance) {
            $entry = $closingData[$balance->currency_id] ?? null;

            if ($entry === null) {
                throw new CaisseSessionException(
                    "Le solde de fermeture pour la devise {$balance->currency->code} est manquant."
                );
            }

            $transactions = Transaction::query()
                ->where('employee_id', $this->employee_id)
                ->where('status', TransactionStatus::Completed->value)
                ->whereHas('account', fn ($q) => $q->where('currency_id', $balance->currency_id))
                ->whereBetween('created_at', [$this->opened_at, now()])
                ->get();

            $deposits = (float) $transactions->where('type', TransactionType::Deposit)->sum('amount');
            $withdrawals = (float) $transactions->where('type', TransactionType::Withdrawal)->sum('amount');
            $other = (float) $transactions
                ->whereNotIn('type', [TransactionType::Deposit, TransactionType::Withdrawal])
                ->sum('amount');

            $expectedClosing = (float) $balance->opening_balance_declared + $deposits - $withdrawals + $other;
            $declared = (float) $entry['declared'];

            $balance->update([
                'total_deposits' => $deposits,
                'total_withdrawals' => $withdrawals,
                'total_other_movements' => $other,
                'closing_balance_expected' => $expectedClosing,
                'closing_balance_declared' => $declared,
                'closing_discrepancy' => round($declared - $expectedClosing, 2),
                'closing_comment' => $entry['comment'] ?? null,
            ]);
        }

        $this->update([
            'closed_at' => now(),
            'status' => CaisseSessionStatus::Closed,
        ]);
    }

    public function hasDiscrepancy(): bool
    {
        if ($this->status !== CaisseSessionStatus::Closed) {
            return false;
        }

        return $this->balances->contains(fn (CaisseSessionBalance $b) => $b->hasDiscrepancy());
    }

    /**
     * Devises en ecart a la fermeture, pour affichage rapide ou generation
     * de rapport (une entree par devise concernee).
     */
    public function discrepantBalances(): \Illuminate\Support\Collection
    {
        return $this->balances->filter(fn (CaisseSessionBalance $b) => $b->hasDiscrepancy());
    }
}