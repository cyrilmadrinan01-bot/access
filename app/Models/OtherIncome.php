<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherIncome extends Model
{
    protected $fillable = ['cutoff_id', 'empnum', 'empname', 'income_type', 'amount', 'is_taxable'];
}
