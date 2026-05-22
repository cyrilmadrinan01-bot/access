<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePersonalInfo extends Model
{
    protected $fillable = [
        'employee_id', 'first_name','last_name','birth_date','gender','bank_name','account_number','effective_start','effective_end','marital_status','salutation','prefix','nationality','religion','country_of_birth'
    ];
}
