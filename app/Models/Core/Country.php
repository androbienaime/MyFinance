<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $guarded;
    
    public function states() : HasMany{
        return $this->hasMany(State::class);
    }
}
