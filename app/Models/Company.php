<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cnpj',
        'company',
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
}
