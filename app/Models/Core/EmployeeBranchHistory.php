<?php
// app/Models/EmployeeSuccursaleHistory.php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EmployeeBranchHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'branch_id',
        'started_at',
        'ended_at',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope pour récupérer l'affectation active à une date donnée.
     * Essentiel pour générer des rapports non biaisés dans le temps.
     */
    public function scopeActiveAt(Builder $query, \DateTimeInterface|string $date): Builder
    {
        return $query
            ->where('started_at', '<=', $date)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('ended_at')
                  ->orWhere('ended_at', '>', $date);
            });
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }
}