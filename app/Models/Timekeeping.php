<?php

namespace App\Models;
use App\Models\TimekeepingCorrections;
use App\Models\Overtime;
use App\Models\Holiday;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Timekeeping extends Model implements AuditableContract
{
    //protected $table = 'timekeepings';
    use Auditable;
    protected $fillable = [
        'empnum',
        'dated',
        'payrollDate',
        'dayType',
        'shiftCode',
        'timeIn',
        'timeOut',
        'correctedShiftCode',
        'correctedTimeIn',
        'correctedTimeOut',
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
        'segments',
        'shiftcode_id',
        'reason_id',
        'leave_id',
    ];

    protected $casts = [
        'dated' => 'date',  
        'payrollDate' => 'date',
        'segments' => 'array',
        'timeIn'   => 'datetime',
        'timeOut'  => 'datetime',
        'correctedTimeIn' => 'datetime',
        'correctedTimeOut' => 'datetime',
        'late' => 'float',
        'undertime' => 'float',
        'overtime' => 'float',
    ];


    public function corrections()
    {
        return $this->hasOne(TimekeepingCorrections::class)
            ->whereIn('status', ['Pending', 'Approved', 'Adjusted'])
            ->latestOfMany();
    }

    public function shiftcode()
    {
        return $this->hasOne(TimekeepingCorrections::class)
            ->whereIn('status', ['Pending', 'Approved', 'Adjusted'])
            ->latestOfMany()
            ->with('shiftCodeRelation'); ;
    }

    public function shift()
    {
        // 'shiftCode' in timekeepings table → 'shiftCode' in shiftcodes table
        return $this->belongsTo(ShiftCode::class, 'shiftCode', 'shiftCode');
    }

    public function activeCorrection()
    {
        return $this->hasOne(TimekeepingCorrections::class)
            ->whereIn('status', ['Pending', 'Approved', 'Ajusted'])
            ->orderByRaw("FIELD(status, 'Pending', 'Approved', 'Adjusted)")
            ->latest();
    }

    public function latestPendingCorrection()
    {
        return $this->hasOne(TimekeepingCorrections::class)
                    ->where('status', 'Pending')
                    ->latest('created_at');
    }

    public function records()
    {
        return $this->hasMany(timekeeping_record::class, 'timekeeping_id', 'id');
    }

    public function overtimes()
    {
        return $this->hasMany(Overtime::class, 'timekeeping_id', 'id');
    }

    public function latestCorrection()
    {
        return $this->hasOne(TimekeepingCorrections::class)
            ->whereIn('status', ['Pending', 'Approved', 'Adjusted'])
            ->latestOfMany();
    }

    public function activeCorrections()
    {
        return $this->hasMany(TimekeepingCorrections::class)
                    ->whereIn('status', ['Pending', 'Approved', 'Adjusted']);
    }

    public function latestActiveCorrection()
    {
        return $this->hasOne(TimekeepingCorrections::class)
                    ->whereIn('status', ['Pending', 'Approved', 'Adjusted'])
                    ->latest('id'); // or latest('created_at')
    }

    public function scopeCurrentCutoff($query)
    {
        if ($cutoff = PayrollCutOff::where('current', 'Yes')->first()) {
            $query->whereDate('payrollDate', $cutoff->payrollDate);
        }
    }

    public function adjustment()
    {
        return $this->hasOne(PayrollAdjustment::class, 'timekeeping_id')
            ->latestOfMany();
    }

}
