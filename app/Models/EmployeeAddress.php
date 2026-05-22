<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAddress extends Model
{
    protected $fillable = [
        'employee_id', 'type','address_line1','address_line2','city','province','postal_code','country','effective_start','effective_end'
    ];
}
