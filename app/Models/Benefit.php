<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Benefit extends Model
{
    use SoftDeletes;

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

    protected $casts = [
        'value' => 'decimal:2',
        'birthday' => 'date',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employees_benefits', 'benefits_id')
                    ->withPivot(['value', 'qtd', 'days', 'work_days', 'paid'])
                    ->withTimestamps();
    }
}
