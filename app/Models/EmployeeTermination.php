<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTermination extends Model
{
    protected $fillable = [
        'employee_id',
        'empnum',
        'employee_name',
        'termination_date',
        'termination_reason',
        'access_termination_date',
    ];
}
