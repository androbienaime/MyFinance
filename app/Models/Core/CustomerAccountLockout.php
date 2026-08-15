<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAccountLockout extends Model
{
    protected $fillable = [
        'identifier', 'failed_count', 'locked_until', 'last_attempt_at',
    ];

    protected $casts = [
        'failed_count' => 'integer',
        'locked_until' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function isCurrentlyLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }
}