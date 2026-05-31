<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\{Timekeeping, timekeeping_record, Shiftcode, Reason, TimekeepingCorrections, Overtime, PayrollCutOff, PayrollAdjustment, EmployeeEmployment, Employee};
use App\Services\TimekeepingCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TimekeepingCorrectionApprovalController extends Controller
{
    public function index()
{
    $user = Auth::user();

    /*
    users.empnum = logged in manager
    employees.empnum = employee number
    employees.managerId = manager empnum
    */

    // Get all employee empnum under current manager
    $subordinates = Employee::where('manager_empnum', $user->empnum)
        ->pluck('empnum');

    return Inertia::render('approvals/TimekeepingCorrections', [

        'pendingCorrections' => TimekeepingCorrections::with([
                'timekeeping',
                'shiftcode',
                'reason',
                'creator',
            ])
            ->whereHas('timekeeping', function ($q) use ($subordinates) {
                $q->whereIn('empnum', $subordinates);
            })
            ->where('status', 'Pending')
            ->orderBy('created_at')
            ->get(),

        'approvedCorrections' => TimekeepingCorrections::with([
                'timekeeping',
                'shiftcode',
                'reason',
                'creator',
            ])
            ->whereHas('timekeeping', function ($q) use ($subordinates) {
                $q->whereIn('empnum', $subordinates);
            })
            ->where('status', 'Approved')
            ->orderBy('approved_at', 'desc')
            ->get(),

        'adjustments' => PayrollAdjustment::with([
                'timekeeping',
                'shiftcode',
                'reason',
                'approvedBy',
            ])
            ->whereHas('timekeeping', function ($q) use ($subordinates) {
                $q->whereIn('empnum', $subordinates);
            })
            ->orderBy('approved_at', 'desc')
            ->get(),
    ]);
}

    public function approve(TimekeepingCorrections $timekeepingCorrection)
    {
        DB::transaction(function () use ($timekeepingCorrection) {
            $this->processCorrections(collect([$timekeepingCorrection]));
        });

        return redirect()
            ->route('approvals.timekeeping')
            ->with('success', 'Correction approved successfully');
    }

    public function reject(Request $request, TimekeepingCorrections $timekeepingCorrection)
    {
        abort_if($timekeepingCorrection->status !== 'Pending', 403);

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $timekeepingCorrection->update([
            'status'            => 'Rejected',
            'rejected_reason'   => $request->reason,
            'approved_by'       => Auth::id(),
            'approved_at'       => now(),
        ]);

        return redirect()
            ->route('approvals.timekeeping')
            ->with('error', 'Correction rejected');
        }
 
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:timekeeping_corrections,id',
        ]);

        $corrections = TimekeepingCorrections::with(['timekeeping','reason'])
            ->whereIn('id', $request->ids)
            ->where('status','Pending')
            ->get();

        DB::transaction(function () use ($corrections) {
            $this->processCorrections($corrections);
        });

        return redirect()
            ->route('approvals.timekeeping')
            ->with('success', 'Selected corrections approved.');
    }

    // Show all approved corrections that can be canceled
    public function approved()
    {
        return Inertia::render('approvals/ApprovedCorrections', [
            'corrections' => TimekeepingCorrections::with(['timekeeping','shiftcode','reason','creator'])
                ->where('status','Approved')
                ->orderBy('approved_at','desc')
                ->get(),
        ]);
    }

    public function rejected()
    {
        return Inertia::render('approvals/ApprovedCorrections', [
            'corrections' => TimekeepingCorrections::with(['timekeeping','shiftcode','reason','creator'])
                ->where('status','Rejected')
                ->orderBy('approved_at','desc')
                ->get(),
        ]);
    }

    // Cancel an approval
    public function cancelApproval(TimekeepingCorrections $timekeepingCorrection)
    {
        abort_if($timekeepingCorrection->status !== 'Approved', 403);
        abort_if(Auth::id() !== $timekeepingCorrection->approved_by, 403);

        $approvedOvertime = $timekeepingCorrection->timekeeping
                                        ->overtimes()
                                        ->where('status', 'Approved')
                                        ->exists();

        if ($approvedOvertime) {
            return redirect()->back()
            ->with('error', 'Cannot cancel this correction approval because there is already an approved overtime for this timekeeping.');
        }

        DB::transaction(function () use ($timekeepingCorrection) {

            $timekeeping = $timekeepingCorrection->timekeeping;

            // 1️⃣ Get last snapshot before approval
            $lastSnapshot = $timekeeping->records()
                ->where('appStatusId', '!=', 'Approved')
                ->latest()
                ->first();

            if ($lastSnapshot) {
                $timekeeping->update([
                    'correctedShiftCode' => $lastSnapshot->shiftCode,
                    'correctedTimeIn'    => $lastSnapshot->timeIn,
                    'correctedTimeOut'   => $lastSnapshot->timeOut,
                    'reason'             => $lastSnapshot->reason,
                    'otherReason'        => $lastSnapshot->otherReason,
                    'regHours'           => $lastSnapshot->regHours,
                    'overtime'           => $lastSnapshot->overtime,
                    'nsd'                => $lastSnapshot->nsd,
                    'late'               => $lastSnapshot->late,
                    'undertime'          => $lastSnapshot->undertime,
                    'hoursWorked'        => $lastSnapshot->hoursWorked,
                    'typeCode'           => $lastSnapshot->typeCode,
                ]);
            }

            // 2️⃣ Delete the approved snapshot record
            $timekeeping->records()
                ->where('correction_id', $timekeepingCorrection->id)
                ->where('appStatusId', 'Approved')
                ->delete();

            // 3️⃣ Reset correction status
            $timekeepingCorrection->update([
                'status' => 'Pending',
                'approved_by' => null,
                'approved_at' => null,
            ]);
        });

        return redirect()->back()
            ->with('success', 'Approval has been canceled successfully.');
        
    }

    public function cancelRejection(TimekeepingCorrections $timekeepingCorrection)
    {
        abort_if($timekeepingCorrection->status !== 'Approved', 403);
        abort_if(Auth::id() !== $timekeepingCorrection->approved_by, 403);

        DB::transaction(function () use ($timekeepingCorrection) {

            $timekeepingCorrection->update([
                'status' => 'Pending',
                'approved_by' => null,
                'approved_at' => null,
            ]);
        });

        return redirect()->back()
            ->with('success', 'Rejection has been canceled successfully.');
        
    }

    private function processCorrections($corrections)
    {
        if ($corrections->isEmpty()) {
            abort(422, 'No pending corrections found.');
        }

        // Current payroll cut-off
        $currentCutOff = PayrollCutOff::where('current', 'Yes')->firstOrFail();

        // Preload shifts to avoid N+1
        $shifts = ShiftCode::whereIn('id', $corrections->pluck('shiftcode_id')->unique())
            ->get()
            ->keyBy('id');

        // Determine approver ID
        $approvedBy = Auth::id() ?? 1; // fallback to admin user ID

        foreach ($corrections as $correction) {

            abort_if($correction->status !== 'Pending', 403);

            $timekeeping = $correction->timekeeping;
            $shift = $shifts[$correction->shiftcode_id] ?? null;

            if (!$shift) {
                throw new \Exception("Shift not found for correction ID {$correction->id}");
            }

            $timeIn  = Carbon::parse($correction->time_in);
            $timeOut = Carbon::parse($correction->time_out);

            // Determine original cut-off for the timekeeping
            $originalCutOff = PayrollCutOff::whereDate('cutOffStart', '<=', $timekeeping->dated)
                ->whereDate('cutOffEnd', '>=', $timekeeping->dated)
                ->first();

            // If the correction belongs to a previous cut-off → it's an adjustment
            $isAdjustment = $originalCutOff && $originalCutOff->id !== $currentCutOff->id;

            // Supersede previous approvals
            if(!$isAdjustment){
                TimekeepingCorrections::where('timekeeping_id', $timekeeping->id)
                    ->where('status', 'Approved')
                    ->update(['status' => 'Superseded', 'is_active' => false]);
            }

            // Calculate corrected values
            $computed = TimekeepingCalculator::calculate($shift, $timeIn, $timeOut);

            // Convert Carbon to strings for DB
            $computedForDB = collect($computed)->map(fn($v) => $v instanceof Carbon ? $v->format('Y-m-d H:i:s') : $v)->toArray();

            // Update original timekeeping if not an adjustment
            if (!$isAdjustment) {
                $timekeeping->update(array_merge([
                    'correctedShiftCode' => $shift->shiftCode,
                    'correctedTimeIn'    => $timeIn->toDateTimeString(),
                    'correctedTimeOut'   => $timeOut->toDateTimeString(),
                    'reason'             => $correction->reason->reasonName,
                    'otherReason'        => $correction->other_reason,
                ], $computedForDB));
            }

            // Snapshot
            timekeeping_record::create(array_merge([
                'timekeeping_id' => $timekeeping->id,
                'correction_id'  => $correction->id,
                'empnum'         => $timekeeping->empnum,
                'dated'          => $timekeeping->dated,
                'payrollDate'    => $timekeeping->payrollDate,
                'dayType'        => $timekeeping->dayType,
                'shiftCode'      => $shift->shiftCode,
                'timeIn'         => $timeIn->toDateTimeString(),
                'timeOut'        => $timeOut->toDateTimeString(),
                'leaveCode'      => $timekeeping->leaveCode,
                'flagStatus'     => $timekeeping->flagStatus,
                'shiftStart'     => $shift->shiftStart,
                'shiftEnd'       => $shift->shiftEnd,
                'source'         => $isAdjustment ? 'Adjustment' : 'Correction',
                'reason'         => $correction->reason->reasonName,
                'otherReason'    => $correction->other_reason,
                'appStatusId'    => 'Approved',
                'flag'           => 'Corrected',
            ], $computedForDB));

            // If adjustment → create PayrollAdjustment
            if ($isAdjustment) {

                

                // If no previous snapshot, treat current timekeeping values as original
                $originalComputed = [
                    'reg_hours'    => $timekeeping->regHours ?? 0,
                    'overtime'     => $timekeeping->overtime ?? 0,
                    'late'         => $timekeeping->late ?? 0,
                    'undertime'    => $timekeeping->undertime ?? 0,
                    'nsd'          => $timekeeping->nsd ?? 0,
                    'hours_worked' => $timekeeping->hoursWorked ?? 0,
                ];

                // Compute deltas
                $delta = [
                    'reg_hours'    => ($computed['regHours'] ?? 0) - $originalComputed['reg_hours'],
                    'overtime'     => ($computed['overtime'] ?? 0) - $originalComputed['overtime'],
                    'late'         => ($computed['late'] ?? 0) - $originalComputed['late'],
                    'undertime'    => ($computed['undertime'] ?? 0) - $originalComputed['undertime'],
                    'nsd'          => ($computed['nsd'] ?? 0) - $originalComputed['nsd'],
                    'hours_worked' => ($computed['hoursWorked'] ?? 0) - $originalComputed['hours_worked'],
                ];

                // Calculate adjusted hours bsed on delta + typeCode
                $adjustedHours = ($delta['reg_hours'] ?? 0) - ($delta['late'] ?? 0) - ($delta['undertime'] ?? 0);
                if (($computed['typeCode'] ?? '') === 'Absent') {
                    $adjustedHours -= 8;
                }

                // Adjusted NSD
                $adjustedNSD = $delta['nsd'] ?? 0;

                PayrollAdjustment::create([
                    'original_correction_id' => $correction->id,
                    'timekeeping_id'         => $timekeeping->id,
                    'payroll_cut_off_id'     => $currentCutOff->id,
                    'empnum'                 => $timekeeping->empnum,
                    'dated'                  => $timekeeping->dated,
                    'shiftcode_id'           => $correction->shiftcode_id,
                    'time_in'                => $timeIn->toDateTimeString(),
                    'time_out'               => $timeOut->toDateTimeString(),
                    'reason_id'              => $correction->reason_id,
                    'other_reason'           => $correction->other_reason,
                    
                    // Decomposed computed
                    'reg_hours'              => $delta['reg_hours'] ?? 0,
                    'overtime'               => $delta['overtime'] ?? 0,
                    'late'                   => $delta['late'] ?? 0,
                    'undertime'              => $delta['undertime'] ?? 0,
                    'nsd'                    => $delta['nsd'] ?? 0,
                    'hours_worked'           => $delta['hours_worked'] ?? 0,
                    
                    // Adjusted columns
                    'adjusted_hours'         => $adjustedHours,
                    'adjusted_nsd'           => $adjustedNSD,
                    
                    'approved_by'            => $approvedBy,
                    'approved_at'            => now(),
                ]);

                // Mark original correction as adjusted
                $correction->update([
                    'status'        => 'Adjusted',
                    'is_adjustment' => 1,
                ]);

                continue;
            }

            // Approve normally
            $correction->update([ 
                'status'             => 'Approved',
                'is_active'          => true,
                'is_adjustment'      => 0,
                'payroll_cut_off_id' => $currentCutOff->id,
                'approved_by'        => $approvedBy,
                'approved_at'        => now(),
            ]);
        }
    }

    public function cancelAdjustment(PayrollAdjustment $adjustment)
    {
        // Load the related correction safely
        $correction = $adjustment->originalCorrection;

        if ($correction) {
            $correction->update([
                'status' => 'Pending',
                'approved_by' => null,
                'approved_at' => null,
            ]);
        }

        // Delete the adjustment
        $adjustment->delete();

        return back()->with('success', 'Adjustment cancelled successfully.');
    }

}
