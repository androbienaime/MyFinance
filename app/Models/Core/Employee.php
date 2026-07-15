<?php

namespace App\Models\Core;

use App\Contracts\Deletable;
use App\Models\Concerns\HasDeletionGuard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Employee extends Model implements Deletable
{
    use HasFactory, SoftDeletes, HasDeletionGuard, Notifiable;

    protected $fillable = [
        'firstname',
        'lastname',
        'user_id',
        'gender',
        'identity_number',
        'branch_id',
        'address_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Flag interne, jamais persisté, jamais accessible depuis l'extérieur
     * autrement que par transferToBranch(). Empêche toute modification
     * directe de branch_id en dehors du workflow de transfert officiel.
     */
    private bool $branchUpdateAuthorized = false;

    protected static function booted(): void
    {
        static::updating(function (Employee $employee) {
            if ($employee->isDirty('branch_id') && ! $employee->branchUpdateAuthorized) {
                throw new \RuntimeException(
                    "La succursale d'un employé ne peut être modifiée que via transferToBranch()."
                );
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->firstname} {$this->lastname}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function fullName(): string
    {
        return trim("{$this->firstname} {$this->lastname}");
    }

    public function customersCreated(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function identityDocuments()
    {
        return $this->morphMany(IdentityDocument::class, 'identity_documentable');
    }

    public function branchHistories(): HasMany
    {
        return $this->hasMany(EmployeeBranchHistory::class);
    }

    public function currentBranchHistory(): HasMany
    {
        return $this->hasMany(EmployeeBranchHistory::class)->whereNull('ended_at');
    }

    /**
     * Transfère l'employé vers une nouvelle Branch, en clôturant
     * proprement l'ancienne affectation et en ouvrant la nouvelle.
     * C'est le SEUL point d'entrée pour changer la Branch d'un employé.
     */
    public function transferToBranch(
        Branch $newBranch,
        ?string $reason = 'transfert',
        ?int $performedBy = null,
        ?Carbon $effectiveDate = null
    ): EmployeeBranchHistory {
        $effectiveDate ??= now();

        if ($this->branch_id === $newBranch->id) {
            throw new \InvalidArgumentException("L'employé est déjà affecté à cette succursale.");
        }

        return DB::transaction(function () use ($newBranch, $reason, $performedBy, $effectiveDate) {
            // 1. Clôturer l'affectation en cours (s'il y en a une)
            $this->branchHistories()
                ->whereNull('ended_at')
                ->update(['ended_at' => $effectiveDate]);

            // 2. Ouvrir la nouvelle affectation historisée
            $history = $this->branchHistories()->create([
                'branch_id' => $newBranch->id,
                'started_at' => $effectiveDate,
                'ended_at' => null,
                'reason' => $reason,
                'created_by' => $performedBy,
            ]);

            // 3. Autoriser temporairement l'update, puis révoquer immédiatement après
            $this->branchUpdateAuthorized = true;

            try {
                $this->update(['branch_id' => $newBranch->id]);
            } finally {
                $this->branchUpdateAuthorized = false;
            }

            return $history;
        });
    }

    /**
     * Retourne la Branch où l'employé se trouvait à une date donnée.
     * À utiliser dans TOUS les rapports historiques.
     */
    public function branchAt(\DateTimeInterface|string $date): ?Branch
    {
        $history = $this->branchHistories()
            ->activeAt($date)
            ->first();

        return $history?->branch;
    }

    public function canBeDeleted(): bool
    {
        return true;
    }

    public function getDeletionGuardMessage(): string
    {
        return "Cet employé ne peut pas être supprimé.";
    }
}