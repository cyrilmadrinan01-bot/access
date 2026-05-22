<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilhealthContribution extends Model
{
    protected $fillable = [
        'cutoff_id',
        'empnum',
        'empname',
        'philhealth_number',
        'year',
        'month',
        'employee',
        'employer',
        'processed_at',
    ];
}
