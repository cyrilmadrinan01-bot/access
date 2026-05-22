<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollYtd extends Model
{
    protected $fillable = [
        'empnum',
        'year',
        'gross_income',
        'taxable_income',
        'withholding_tax',
        'sss_employee',
        'philhealth_employee',
        'pagibig_employee',
        'net_pay',
    ];
}
