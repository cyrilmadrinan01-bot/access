<?php

namespace App\Services;

use App\Models\LeaveAccrual;
use App\Models\EmployeeLeaveBalance; 
use Illuminate\Support\Facades\DB;

class LeaveAccrualService
{
    public static function accrue(
        int $empnum,
        int $leaveTypeId,
        float $amount,
        string $type = 'Manual',
        ?string $remarks = null,
        ?int $adminId = null
    ) {
        DB::transaction(function () use (
            $empnum, $leaveTypeId, $amount, $type, $remarks, $adminId
        ) {
            $balance = EmployeeLeaveBalance::firstOrCreate(
                [
                    'empnum' => $empnum,
                    'leave_type_id' => $leaveTypeId,
                ],
                ['balance' => 0]
            );

            $balance->increment('balance', $amount);

            LeaveAccrual::create([
                'empnum' => $empnum,
                'leave_type_id' => $leaveTypeId,
                'amount' => $amount,
                'accrual_type' => $type,
                'remarks' => $remarks,
                'accrued_by' => $adminId,
            ]);
        });
    }
}
