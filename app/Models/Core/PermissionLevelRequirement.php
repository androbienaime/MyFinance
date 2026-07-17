<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class PermissionLevelRequirement extends Model
{
    protected $fillable = [
        'permission_id',
        'min_level_to_assign'
    ];

    protected $casts = [
        'min_level_to_assign' => 'integer'
    ];

    public function permission(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
