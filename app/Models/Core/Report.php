<?php

namespace App\Models\Core;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'category',
        'title',
        'content',
        'data',
        'employee_id',
        'branch_id',
        'period_start',
        'period_end',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
    ];

    protected $casts = [
        'type' => ReportType::class,
        'category' => ReportCategory::class,
        'status' => ReportStatus::class,
        'data' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('type', ReportType::Automatic->value);
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('type', ReportType::Manual->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Pending->value);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Marque le rapport comme revu par un administrateur. Point d'entree
     * unique pour cette transition, afin que reviewed_by/reviewed_at
     * restent toujours coherents entre eux.
     */
    public function markReviewed(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => ReportStatus::Reviewed,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'reviewer_notes' => $notes,
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => ReportStatus::Archived]);
    }
}