<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankFile extends Model
{
    protected $fillable = [
        'cutoff_id',
        'empnum',
        'employee_name',
        'account_number',
        'amount',
        'reference_number',
    ];
}
