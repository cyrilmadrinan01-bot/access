<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TimekeepingCorrections;
use App\Models\Timekeeping;
use App\Models\PayrollCutOff;
use App\Models\ShiftCode;
use App\Models\Reason;
use App\Models\User;

class PayrollAdjustment extends Model
{
    protected $table = 'payroll_adjustments';

    protected $fillable = [
        'original_correction_id',
        'timekeeping_id',
        'payroll_cut_off_id',
        'empnum',
        'dated',
        'shiftcode_id',
        'time_in',
        'time_out',
        'reason_id',
        'other_reason',
        'reg_hours',
        'overtime',
        'late',
        'undertime',
        'nsd',
        'hours_worked',
        'adjusted_hours',
        'adjusted_nsd',
        'approved_by',
        'approved_at',
    ];

    // --- Relationships ---

    // Link to the original correction
    public function originalCorrection()
    {
        return $this->belongsTo(TimekeepingCorrections::class, 'original_correction_id');
    }

    // Link to the timekeeping record
    public function timekeeping()
    {
        return $this->belongsTo(Timekeeping::class, 'timekeeping_id');
    }

    // Link to the shiftcode
    public function shiftcode()
    {
        return $this->belongsTo(Shiftcode::class, 'shiftcode_id');
    }

    // Link to the reason
    public function reason()
    {
        return $this->belongsTo(Reason::class, 'reason_id');
    }

    // Link to the user who approved
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Optional: link to payroll cut-off
    public function payrollCutOff()
    {
        return $this->belongsTo(PayrollCutOff::class, 'payroll_cut_off_id');
    }
}





