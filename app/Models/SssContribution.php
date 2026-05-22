<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SssContribution extends Model
{
    protected $fillable = [
        'cutoff_id', 'empnum','empname','sss_number','year','month','employee','employer','ec','processed_at'
    ];
}
