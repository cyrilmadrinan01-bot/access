<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDeduction extends Model
{
    protected $fillable = [
        'cutoff_id',
        'empnum',
        'empname',
        'deduction_type',
        'amount',
        'is_pre_tax',
        'uploaded_at'
    ];
}
