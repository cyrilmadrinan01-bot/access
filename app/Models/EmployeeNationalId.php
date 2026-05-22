<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeNationalId extends Model
{
    protected $table = 'employee_national_ids';

    protected $fillable = [
        'employee_id', 'tin','sss','pagibig','philhealth'
    ];
}
