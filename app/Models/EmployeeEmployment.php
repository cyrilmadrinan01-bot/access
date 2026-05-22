<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeEmployment extends Model
{
    protected $fillable = [
        'employee_id', 'company_code','department_code','job_code','manager_empnum','status','hire_date','termination_date','effective_start','effective_end','business_unit','division','cost_center','channel_code','line_code','project','account_code','intercompany','regular_temp'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
 