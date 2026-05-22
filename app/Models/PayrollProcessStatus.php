<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollProcessStatus extends Model
{
    protected $fillable = [
        'cutoff_id',
        'step',
        'status',
        'processed_at'
    ];
}
