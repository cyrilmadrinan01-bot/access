<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biometric extends Model
{
    protected $table = 'biometrics';

    protected $fillable = [
        'empnum',
        'timeLog',
        'deviceIp',
        'dayName',
        'dated',
        'processed',
        'logType',
        'retry_count',
        'last_error',
    ];

    protected $dates = ['timeLog', 'dated'];

    public $timestamps = true;

}
