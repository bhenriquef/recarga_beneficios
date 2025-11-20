<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Absenteeism extends Model
{
    use SoftDeletes;

    protected $table = 'absenteeism';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'reason',
        'solides_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
