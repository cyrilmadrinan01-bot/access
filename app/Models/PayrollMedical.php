<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollMedical extends Model
{
    protected $fillable = [
        'cutoff_id',
        'empnum',
        'empname',
        'total_amount',
        'processed_at',
    ];
}
