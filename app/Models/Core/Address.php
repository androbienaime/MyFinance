<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{

        protected $fillable = [
        'country_id',
        "state_id",
        'city_id',
        "city_2",
        "phone",
        "phone_mobile",
        "email",
        "phonecode",
        "address1",
        "address2",
        "alias",
        "company",
        "active"
    ];

    public function getFullAddressAttribute(){
        return "{$this->city?->name}, {$this->state?->name}, {$this->country?->name}";
    }

    public function country() : BelongsTo{
        return $this->belongsTo(Country::class);
    }
    public function state() : BelongsTo{
        return $this->belongsTo(State::class);
    }

    public function city() : BelongsTo{
        return $this->belongsTo(City::class);
    }
}
