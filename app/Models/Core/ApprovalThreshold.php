<?php

namespace App\Models\Core;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalThreshold extends Model
{
     use HasFactory;

    protected $fillable = [
        'type',
        'min_amount',
        'required_levels',
        'is_active',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'min_amount' => 'decimal:2',
        'required_levels' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Nombre de niveaux d'approbation requis pour un montant donne :
     * on prend le seuil actif le plus eleve que le montant depasse.
     * Retourne 0 si aucun seuil ne s'applique (execution directe).
     */
    public static function levelsRequiredFor(TransactionType $type, float $amount): int
    {
        return static::query()
            ->where('type', $type->value)
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->orderByDesc('min_amount')
            ->value('required_levels') ?? 0;
    }
}

