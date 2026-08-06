<?php

namespace App\Models\Core;

use App\Contracts\Deletable;
use App\Enums\AccountHolderType;
use App\Enums\TransactionDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Concerns\HasDeletionGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Account extends Model implements Deletable
{
    use HasFactory, SoftDeletes, HasDeletionGuard;

    protected $fillable = [
        'code',
        'type_of_account_id',
        'customer_id',
        'currency_id',
        'balance',
        'is_active',
        'employee_id',
        'holder_type'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'holder_type' => AccountHolderType::class
    ];

    public function typeOfAccount(): BelongsTo
    {
        return $this->belongsTo(TypeOfAccount::class);
    }

    public function tagsPayments(): HasMany{
        return $this->hasMany(TagsPayment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function merchantProfile(): HasOne
    {
        return $this->hasOne(MerchantProfile::class);
    }

    public function isMerchant(): bool
    {
        return $this->holder_type === AccountHolderType::Merchant;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function people() : BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'account_person')
            ->withPivot([
                'role',
                'permissions',
                'share_percentage',
                'start_date',
                'end_date',
                'is_active'
            ])
            ->withTimestamps();
    }

    public function accountPeople(): HasMany
    {
        return $this->hasMany(AccountPerson::class);
    }
    /**
     * Insertion optimiste + retry sur collision, plutot qu'un
     * "check puis create" separe qui est vulnerable a une double
     * ecriture concurrente (c'etait la faille dans l'ancien projet).
     */
    public static function generateUniqueCode(TypeOfAccount $typeOfAccount, int $attempts = 5): string
    {
        for ($i = 0; $i < $attempts; $i++) {
            $candidate = $typeOfAccount->prefix.'-'.random_int(1000, 9999);

            if (! static::withTrashed()->where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Impossible de generer un code de compte unique apres plusieurs tentatives.');
    }


    public function canBeDeleted(): bool
    {
        return ! $this->transactions()->exists();
    }

    public function getDeletionGuardMessage(): string
    {
        $count = $this->transactions()->count();

        return "Ce compte possède {$count} transaction(s) et ne peut pas être supprimé.";
    }


    public function closures(): HasMany
    {
        return $this->hasMany(AccountClosure::class);
    }

    /**
     * Ferme manuellement le compte. La raison est optionnelle.
     * Point d'entrée unique pour toute fermeture manuelle.
     */
    public function closeAccount(?string $reason = null, ?int $performedBy = null): AccountClosure
    {
        if (! $this->is_active) {
            throw new \RuntimeException("Ce compte est déjà fermé.");
        }

        return DB::transaction(function () use ($reason, $performedBy) {
            $closure = $this->closures()->create([
                'type' => TransactionType::AccountClosure,
                'reason' => $reason,
                'balance_at_closure' => $this->balance ?? 0,
                'closed_by' => $performedBy,
            ]);

            $this->update([
                'is_active' => false,
                'closed_at' => now(),
            ]);

            return $closure;
        });
    }

    public function pendingDebitsTotal(): float
    {
        return (float) $this->transactions()
            ->where('status', TransactionStatus::Pending->value)
            ->where(function ($q) {
                $q->where('type', TransactionType::Withdrawal->value)
                    ->orWhere(function ($q2) {
                        $q2->where('type', TransactionType::Transfer->value)
                            ->where('direction', TransactionDirection::Debit->value);
                    });
            })
            ->sum('amount');
    }

    public function availableBalance(): float
    {
        return (float) $this->balance - $this->pendingDebitsTotal();
    }


    // app/Models/Core/Account.php — ajouts (remplace les deux methodes precedentes)
    /**
     * Determine si un reglement de ce compte, MAINTENANT, constitue un
     * retrait anticipe (donc soumis a frais). Seul signal utilise :
     * type->duration > 0 - au-dela, la nature du compte (a cases ou a
     * maturite) dicte COMMENT interpreter cette duree :
     * - active_case_payments : duration = nombre de cases requises.
     * - sinon : duration = nombre de mois requis depuis l'ouverture.
     * duration <= 0 (ou null) => jamais de penalite, peu importe le type.
     */
    public function isEarlySettlement(): bool
    {
        $type = $this->typeOfAccount;
        $duration = (int) ($type->duration ?? 0);

        if ($duration <= 0) {
            return false;
        }

        // Comparaison sur la date seule, sans les heures : le jour exact de
        // maturite ne doit jamais compter comme "anticipe", peu importe
        // l'heure a laquelle le reglement est effectue ce jour-la.
        $maturityDate = $this->created_at->copy()->startOfDay()->addMonths($duration);
        $today = now()->startOfDay();

        $maturityNotReached = $today->lt($maturityDate);

        if ((bool) $type->active_case_payments) {
            $paidCount = $this->tagsPayments()->count();
            $casesNotFilled = $paidCount < $duration * 30;

            // dd($maturityNotReached);
            return $casesNotFilled && $maturityNotReached;
        }

        return $maturityNotReached;
    }

    public function earlyWithdrawalFeeAmount(): float
    {
        if (! $this->isEarlySettlement()) {
            return 0.0;
        }

        $percentage = $this->typeOfAccount->earlyWithdrawalFeePercentage();

        return round(((float) $this->balance) * $percentage / 100, 2);
    }


    public function getAccountInfos(){
        if($this->accountPeople === null){
            return "Aucune personne n'est associer a ce compte";
        }

        return $lines = $this->accountPeople
            ->values()
            ->map(function ($accountPerson, $index) {
                $person = $accountPerson->person;

                $document = $person->identityDocuments
                    ->sortByDesc('is_primary')
                    ->first();

                $line = ($index + 1) . ". {$person->full_name}";

                if ($document) {
                    $line .= " - {$document->document_type} : {$document->document_number}";
                }

                $permissions = implode(', ', $accountPerson->permissions ?? []);
                $line .= " - {$accountPerson->role} [{$permissions}]";

                // à adapter : quel(s) rôle(s) doivent afficher le %
                if ($accountPerson->role === 'attorney') {
                    $line .= " : ({$accountPerson->share_percentage}%)";
                }

                $line .= " {$accountPerson->end_date}";

                return $line;
            })
            ->implode("\n");
    }
}
