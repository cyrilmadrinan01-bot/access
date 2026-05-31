<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimekeepingCorrections extends Model
{
    protected $table = 'timekeeping_corrections';

    protected $fillable = [
        'timekeeping_id',
        'payroll_cut_off_id',
        'shiftcode_id',
        'reason_id',
        'time_in',
        'time_out',
        'other_reason',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'is_adjustment',
        'is_active',
    ];

    protected $casts = [
        'time_in'      => 'datetime',
        'time_out'     => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function timekeeping()
    {
        return $this->belongsTo(Timekeeping::class);
    }

    public function shiftcode()
    {
        return $this->belongsTo(Shiftcode::class);
    }

    public function shiftCodeRelation()
    {
        return $this->belongsTo(Shiftcode::class, 'shiftcode_id');
    }

    public function reason()
    {
        return $this->belongsTo(Reason::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
 
    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by', 'empnum');
    }

    public function payrollCutOff()
    {
        return $this->belongsTo(PayrollCutOff::class);
    }

//    public function employee()
//    {
//        return $this->belongsTo(Employee::class, 'empnum', 'empnum');
//    }

    public function employee()
    {
        return $this->hasOneThrough(
            Employee::class,
            User::class,
            'id',        // users.id
            'empnum',    // employees.empnum
            'created_by', // corrections.created_by
            'empnum'      // users.empnum
        );
    }

}
