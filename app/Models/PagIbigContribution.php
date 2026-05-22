<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagIbigContribution extends Model
{
    protected $fillable = [
        'cutoff_id',
        'empnum',
        'empname',
        'pagibig_number',
        'year',
        'month',
        'employee',
        'employer',
        'processed_at',
    ];
}
