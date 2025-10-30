<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeesBenefitsMonthly extends Model
{
    use SoftDeletes;

    protected $table = 'employees_benefits_monthly';

    protected $fillable = [
        'employee_benefit_id',
        'value',
        'qtd',
        'work_days',
        'total_value',
        'paid',
        'date',
    ];
}
