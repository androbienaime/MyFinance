<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    protected $fillable = [
        'last_name',
        'first_name',
        'gender',
        'employee_id',
    ];

    protected $appends = [
        'full_name',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
    
    public function accounts() : BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_person')
            ->withPivot([
                'role',
                'permissions',
                'share_percentage',
                'start_date',
                'end_date',
                'is_active'
            ])
            ->withTimestamps();
    }

    // Dans chaque modèle qui peut avoir des adresses
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function identityDocuments()
    {
        return $this->morphMany(IdentityDocument::class, 'identity_documentable');
    }

    // App\Models\Core\Person.php

    public function accountPeople(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AccountPerson::class);
    }
}
