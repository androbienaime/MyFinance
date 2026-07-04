<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $guarded;

    public function cities() : HasMany{
        return $this->hasMany(City::class);
    }
}
