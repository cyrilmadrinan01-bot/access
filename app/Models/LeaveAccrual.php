<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveAccrual extends Model
{
    protected $fillable = [
        'empnum',
        'leave_type_id',
        'amount',
        'accrual_type',
        'remarks',
        'accrued_by'
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empnum', 'empnum');
    }
}
