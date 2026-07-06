<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'firstname',
        'gender',
        'identity_number',
        'employee_id',
        'address_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    // Dans chaque modèle qui peut avoir des adresses
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Visibilite globale volontaire : un client cree dans une autre
     * succursale reste visible, mais ceux de la succursale/l'employe
     * courant remontent en premier. C'est le point cle qui distingue
     * ce modele d'un scope de tenant classique.
     */
    public function scopeOrderByRelevanceTo(Builder $query, int $currentBranchId, int $currentEmployeeId): Builder
    {
        return $query
            ->leftJoin('employees', 'employees.id', '=', 'customers.employee_id')
            ->selectRaw('customers.*')
            ->orderByRaw('CASE WHEN customers.employee_id = ? THEN 0 ELSE 1 END', [$currentEmployeeId])
            ->orderByRaw('CASE WHEN employees.branch_id = ? THEN 0 ELSE 1 END', [$currentBranchId])
            ->orderByDesc('customers.created_at');
    }
}
