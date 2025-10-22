<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EmployeesBenefits extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'benefits_id',
        'value',
        'qtd',
        'days',
        'work_days',
        'paid',
    ];
}
