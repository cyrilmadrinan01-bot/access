<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBenefit extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'frequency',
        'taxable',
        'amount',
        'payday',
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

    public function benefit()
    {
        return $this->belongsTo(Benefit::class);
    }
}
