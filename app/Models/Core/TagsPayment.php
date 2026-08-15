<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagsPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'tags',
        'account_id',
        'transaction_id'
    ];
    public function Account(){
        return $this->belongsTo(Account::class);
    }

    // public function transaction(){
    //     return $this->hasMany(Transaction::class);
    // }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
