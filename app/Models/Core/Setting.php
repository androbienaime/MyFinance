<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        "key",
        "branch_id",
        "value",
        "updated_by"
    ];

    protected $casts = [
        'value' => 'array', // ou 'json' selon ta preference
    ];
}
