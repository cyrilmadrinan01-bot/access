<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shiftcode extends Model
{
    protected $table = 'shiftcodes';
    protected $fillable = [
        'shiftCode',
        'shiftStart',
        'shiftEnd',
        'hoursWorked',
        'withNd',
        'ndStart',
        'ndEnd',
        'regHours', 
        'ndHours',        
        'workDay',
        'restDay',
        'usShift',
        'sameDay',
        'ndCrossDayStart',
        'ndCrossDayEnd',
        'rotatingShift',
        'group',
        'is_active',
    ];

    protected $appends = ['shift_start', 'shift_end'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(UserShift::class, 'shiftcode_id');
    }

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'shiftcode_id');
    }

    public function restDays(): array
    {
        return array_map(
            'trim',
            explode(',', $this->restDay)
        );
    }

    public function getShiftStartAttribute()
    {
        return $this->attributes['shiftStart'] ?? null;
    }

    public function getShiftEndAttribute()
    {
        return $this->attributes['shiftEnd'] ?? null;
    }
}
