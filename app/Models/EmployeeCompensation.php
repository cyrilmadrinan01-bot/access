<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCompensation extends Model
{
    protected $table = 'employee_compensations';
    
    protected $fillable = [
        'employee_id',
        'base_salary',
        'pay_grade',
        'pay_type',
        'factor',
        'effective_start',
        'effective_end',
    ];

    protected $casts = [
        'effective_start' => 'date',
        'effective_end' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
