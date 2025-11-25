<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BalanceManagement extends Model
{
    use SoftDeletes;

    protected $table = 'balance_management';

    protected $fillable = [
        'requested_value',
        'accumulated_balance',
        'value_economy',
        'final_order_value',
        'date',
        'work_days',
        'benefits_id',
        'employee_id',
    ];

    protected $casts = [
        'requested_value' => 'float',
        'accumulated_balance' => 'float',
        'value_economy' => 'float',
        'final_order_value' => 'float',
        'date' => 'date',
        'work_days' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function benefit()
    {
        return $this->belongsTo(Benefit::class, 'benefits_id');
    }
}
