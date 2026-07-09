<?php

namespace App\Models\Core;

use App\Contracts\Deletable;
use App\Models\Concerns\HasDeletionGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model implements Deletable
{
    use HasFactory, SoftDeletes, HasDeletionGuard;

    protected $fillable = [
        'code',
        'type_of_account_id',
        'customer_id',
        'balance',
        'is_active',
        'employee_id',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
}
