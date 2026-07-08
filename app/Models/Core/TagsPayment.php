<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function transaction(){
        return $this->hasMany(Transaction::class);
    }

}
