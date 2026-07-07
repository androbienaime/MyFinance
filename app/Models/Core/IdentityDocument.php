<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class IdentityDocument extends Model
{
    protected $fillable = [
        'document_type',
        'document_number',
        'issued_date',
        'expiry_date',
        'is_primary',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
