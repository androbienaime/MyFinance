<?php

// app/Models/Core/TrustedDevice.php
namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TrustedDevice extends Model
{
    protected $fillable = [
        'user_id', 'fingerprint', 'ip_address', 'user_agent',
        'login_count', 'is_trusted', 'first_seen_at', 'last_seen_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Devine un nom d'appareil lisible depuis le user-agent */
    public function getDeviceLabelAttribute(): string
    {
        $ua = $this->user_agent;

        return match (true) {
            str_contains($ua, 'Mobile') && str_contains($ua, 'Android') => 'Android Mobile',
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Windows') => 'Windows PC',
            str_contains($ua, 'Macintosh') => 'Mac',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Appareil inconnu',
        };
    }
}