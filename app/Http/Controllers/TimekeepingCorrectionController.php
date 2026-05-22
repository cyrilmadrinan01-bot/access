<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Models\{Timekeeping, timekeeping_record, Shiftcode, Reason, TimekeepingCorrections, User};
use App\Services\TimekeepingCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TimekeepingCorrectionController extends Controller
{
    public function formData()
    {
        return response()->json([
            'shiftCodes' => Shiftcode::select(
                'id',
                'shiftCode'   // ✅ EXACT column name
            )->get(),

            'reasons' => Reason::select(
                'id',
                'reasonType',
                'reasonName'
            )
            ->where('status', 'Active')
            ->get(),
        ]);
    }


    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'timekeeping_id' => 'required|exists:timekeepings,id',
            'shiftCode'      => 'required|exists:shiftcodes,id',
            'reason'         => 'required|exists:reasons,id',
            'timeIn'         => 'required|date',
            'timeOut'        => 'required|date|after:timeIn',
            'otherReason'    => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {

            $timekeepingId = $validated['timekeeping_id'];

            // Supersede previous pending corrections
            TimekeepingCorrections::where('timekeeping_id', $timekeepingId)
            ->where('status', 'Pending')
            ->update([
                'status' => 'Superseded',
                'updated_at' => now(),
            ]);

            timekeeping_record::where('timekeeping_id', $timekeepingId)
            ->where('appStatusId', 'Pending')
            ->update([
                'appStatusId' => 'Superseded',
                'updated_at' => now(),
            ]);

            $timekeeping = Timekeeping::lockForUpdate()->findOrFail($timekeepingId);
            $shift  = Shiftcode::findOrFail($validated['shiftCode']);
            $reason = Reason::findOrFail($validated['reason']);

            $timeIn  = Carbon::parse($validated['timeIn']);
            $timeOut = Carbon::parse($validated['timeOut']);

            //$calc = TimekeepingCalculator::calculate($shift, $timeIn, $timeOut);

            /* ---------- AUDIT TRAIL ---------- */
            TimekeepingCorrections::create([
                'timekeeping_id' => $timekeepingId,
                'shiftcode_id'  => $validated['shiftCode'],
                'reason_id'      => $validated['reason'],
                'other_reason'   => $reason->reasonType === 'Others'
                                    ? $validated['otherReason']
                                    : null,
                'time_in'        => $validated['timeIn'],
                'time_out'       => $validated['timeOut'],
                'status'         => 'Pending',
                'created_by'     => Auth::id(),
            ]);

            /* ---------- UPDATE TIMEKEEPINGS ---------- */
            $timekeeping->update([
                'correctedShiftCode'    => $shift->shiftCode,
                'correctedTimeIn'       => $timeIn,
                'correctedTimeOut'      => $timeOut,
                'reason'                => $reason->reasonName,
                'otherReason'           => $validated['otherReason'] ?? null,
                'corrected_shiftcode_id'=> $validated['shiftCode'],
                'reason_id'             => $validated['reason'],
                
            ]);

            /* ---------- SNAPSHOT ---------- */
            timekeeping_record::create([
                'timekeeping_id' => $timekeeping->id,
                'empnum'         => $timekeeping->empnum,
                'dated'          => $timekeeping->dated,
                'payrollDate'    => $timekeeping->payrollDate,
                'dayType'        => $timekeeping->dayType,
                'shiftCode'      => $shift->shiftCode,
                'timeIn'         => $timeIn,
                'timeOut'        => $timeOut,
                'typeCode'       => $timekeeping->typeCode,
                'leaveCode'      => $timekeeping->leaveCode,
                'flagStatus'     => $timekeeping->flagStatus,
                'source'         => 'Correction',
                'shiftStart'     => $shift->shiftStart,
                'shiftEnd'       => $shift->shiftEnd,
                'reason'         => $reason->reasonName,
                'otherReason'    => $validated['otherReason'] ?? null,
                'appStatusId'    => 'Pending',
                'flag'           => 'Corrected',
                
            ]);
        });

        return redirect()->route('timekeeping')
                ->with('success','Correction submitted successfully');
        }

    public function approve(TimekeepingCorrections $correction)
    {
        $correction->update(['status' => 'Approved']);

        timekeeping_record::where('timekeeping_id', $correction->timekeeping_id)
            ->latest()
            ->first()
            ->update(['appStatusId' => 'Approved']);
    }

    public function deleteCorrection(Timekeeping $timekeeping, Request $request)
{
    DB::transaction(function () use ($timekeeping) {

        /** 1. Get latest correction */
        $correction = $timekeeping->corrections()
            ->where('status', 'Pending')
            ->latest()
            ->first();

        if (!$correction) {
            abort(404, 'No pending correction found');
        }

        /** 2. Insert history record (Deleted) */
        \App\Models\timekeeping_record::create([
            'correction_id' => $correction->id,
            'timekeeping_id' => $timekeeping->id,
            'empnum' => $timekeeping->empnum,
            'dated' => $timekeeping->dated,
            'payrollDate' => $timekeeping->payrollDate,
            'dayType' => $timekeeping->dayType,
            'shiftCode' => $timekeeping->shiftCode,
            'timeIn' => $timekeeping->timeIn,
            'timeOut' => $timekeeping->timeOut,
            'typeCode' => $timekeeping->typeCode,
            'regHours' => $timekeeping->regHours ?? 0,
            'overtime' => $timekeeping->overtime ?? 0,
            'nsd' => $timekeeping->nsd ?? 0,
            'late' => $timekeeping->late ?? 0,
            'undertime' => $timekeeping->undertime ?? 0,
            'leaveCode' => $timekeeping->leaveCode,
            'flagStatus' => $timekeeping->flagStatus,
            'hoursWorked' => $timekeeping->hoursWorked ?? 0,
            'source' => $timekeeping->source,
            'shiftStart' => $timekeeping->shiftStart,
            'shiftEnd' => $timekeeping->shiftEnd,
            'reason' => $timekeeping->reason,
            'otherReason' => $timekeeping->otherReason,
            'appStatusId' => 'Deleted',
        ]);

        /** 3. Clear correction fields */
        $timekeeping->update([
            'correctedShiftCode' => null,
            'correctedTimeIn' => null,
            'correctedTimeOut' => null,
            'corrected_shiftcode_id' => null,
            'reason' => null,
            'reason_id' => null,
            'otherReason' => null,
        ]);

        /** 4. Update correction status */
        $correction->update([
            'status' => 'Deleted',
        ]);
    });

    // Return JSON if AJAX request
    if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Correction deleted successfully',
        ]);
    }

    // Otherwise, fallback to redirect (normal web request)
    return redirect()->route('timekeeping')
        ->with('success', 'Correction deleted successfully');
}


    public function history($timekeepingId)
    {
        return TimekeepingCorrections::with([
            'reason',
            'creator:id,name',
            'approver:id,name'
        ])
        ->where('timekeeping_id', $timekeepingId)
        ->latest()
        ->get();
    }
}

