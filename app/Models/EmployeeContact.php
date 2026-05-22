<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeContact extends Model
{
    protected $fillable = [
        'employee_id', 'type','email','phone','mobile','contact_person','contact_person_number'
    ];
}
