<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'active',
        'full_name',
        'cod',
        'email',
        'cpf',
        'rg',
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
    ];

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
}
