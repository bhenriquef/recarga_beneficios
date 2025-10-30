<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'cnpj',
        'company',
        'from',
        'cod',
        'street',
        'number',
        'complement',
        'cep',
        'neighborhood',
        'city',
        'state',
        'user_id',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
