<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'active',
        'full_name',
        'email',
        'cod_solides',
        'cod_vr',
        'rg',
        'cpf',
        'birthday',
        'mother_name',
        'position',
        'department',
        'address',
        'holiday_next_month',
        'recurring_absence',
        'shutdown_programming',
        'company_id',
        'user_id',
        'admission_date',
        'shutdown_date',
    ];

    protected $casts = [
        'active' => 'boolean',
        'birthday' => 'date',
    ];

    // 🔗 Relacionamentos
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function benefits()
    {
        return $this->belongsToMany(Benefit::class, 'employees_benefits')
                    ->withPivot(['value', 'qtd', 'days', 'work_days', 'paid'])
                    ->withTimestamps();
    }

    public function absenteeisms()
    {
        return $this->hasMany(Absenteeism::class);
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function workdays()
    {
        return $this->hasMany(Workday::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
