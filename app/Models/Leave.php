<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

use Carbon\Carbon;
use App\Models\Timekeeping;

class Leave extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = [
        'empnum',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'hours',
        'reason',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function user()
    {
        return $this->belongsTo(User::class, 'empnum', 'empnum');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function applyToTimekeeping()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return;
        }

        $start = $this->start_date->copy();
        $end   = $this->end_date->copy();

        // Get all days in leave range
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->addDay());

        foreach ($period as $date) {
            $reasonId = \App\Models\Reason::where('reasonName', 'On Leave')->value('id');
            // Update or create timekeeping entry
            Timekeeping::updateOrCreate(
                [
                    'empnum' => $this->user->empnum, // assuming your User model has empnum
                    'dated' => $date->format('Y-m-d'),
                ],
                [
                    'typeCode' => 'onLeave',
                    'leaveCode' => $this->leaveType->code ?? 'Leave',
                    'regHours' => $this->hours,
                    'hoursWorked' => $this->hours,
                    'flagStatus' => 'Approved',
                    'reason' => 'On Leave',
                    'otherReason' => $this->leaveType->name ?? 'Leave',
                    'leave_id'    => $this->id,
                    'reason_id' => $reasonId,
                ]
            );
        }
    }

    public function removeFromTimekeeping()
    {
        Timekeeping::where('leave_id', $this->id)->update([
            'typeCode'    => 'Absent',
            'leaveCode'   => null,
            'flagStatus'  => null,
            'regHours'    => null,
            'hoursWorked' => null,
            'reason'      => null,
            'reason_id'   => null,
            'otherReason' => null,
            'leave_id'    => null,
        ]);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empnum', 'empnum');
    }
}
