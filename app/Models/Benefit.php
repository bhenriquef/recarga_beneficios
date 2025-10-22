<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cod',
        'description',
        'region',
        'operator',
        'value',
        'variable',
        'type',
        'rg',
        'birthday',
        'mother_name',
        'address',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employees_benefits')
            ->withPivot(['value', 'qtd', 'days', 'work_days', 'paid'])
            ->withTimestamps();
    }
}
