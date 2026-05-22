<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class timekeeping_record extends Model
{
    protected $fillable = [
        'timekeeping_id',
        'empnum',
        'dated',
        'payrollDate',
        'dayType',
        'shiftCode',
        'timeIn',
        'timeOut',
        'typeCode',
        'regHours',
        'overtime',
        'nsd',
        'late',
        'undertime',
        'leaveCode',
        'flagStatus',
        'hoursWorked',
        'source',
        'shiftStart',
        'shiftEnd',
        'reason',
        'otherReason',
        'appStatusId',
        'flag'
    ];
}
