<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\Timekeeping;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $empnum = $request->user()->empnum;
        $overtimes = Overtime::where('empnum', $empnum)
            ->orderByDesc('overtimeDate')
            ->get();

        return inertia('overtime/Index', [
            'overtimes' => $overtimes
        ]);
    } 

    public function byTimekeeping($id)
    {
        return Overtime::where('timekeeping_id', $id)
            ->where('status', '!=', 'Deleted')
            ->orderBy('startTime')
            ->get();
    }

    // Create OT
    public function store(Request $request)
    {
        $validated = $request->validate([
            'timekeeping_id' => 'required|exists:timekeepings,id',
            'startTime' => 'required|date',
            'endTime' => 'required|date|after:startTime',
            'reasons' => 'required|string'
        ]);

        $timekeeping = Timekeeping::with('corrections')->findOrFail($validated['timekeeping_id']);

        $start = Carbon::parse($validated['startTime']);
        $end   = Carbon::parse($validated['endTime']);
        $baseDate = $start->copy()->startOfDay();

        //$timeIn  = Carbon::parse($timekeeping->correctedTimeIn ?? $timekeeping->timeIn);
        //$timeOut = Carbon::parse($timekeeping->correctedTimeOut ?? $timekeeping->timeOut);
        $timeIn = Carbon::parse(
            $timekeeping->correctedTimeIn ?? $timekeeping->timeIn
        )->setDateFrom($baseDate);

        $timeOut = Carbon::parse(
            $timekeeping->correctedTimeOut ?? $timekeeping->timeOut
        )->setDateFrom($baseDate);

        // ⏭ Night attendance (timeOut is next day)
        if ($timeOut <= $timeIn) {
            $timeOut->addDay();
        }

        $shiftStart = Carbon::parse($timekeeping->shiftStart)->setDateFrom($baseDate);
        $shiftEnd   = Carbon::parse($timekeeping->shiftEnd)->setDateFrom($baseDate);

        // 🌙 Night shift
        if ($shiftEnd <= $shiftStart) {
            $shiftEnd->addDay();
        }

        // Check if dayType is holiday or special day
        $holidayTypes = ['LH', 'SH', 'LHRD', 'SHRD', 'RD'];
        $isHolidayOrSpecial = in_array($timekeeping->dayType, $holidayTypes);

        if ($start < $timeIn || $end > $timeOut) {
                throw ValidationException::withMessages([
                    'overtime' => 'Overtime must be within Time In and Time Out.',
                ]);
            }

        if (!$isHolidayOrSpecial) {

            $overlapsShift = $start < $shiftEnd && $end > $shiftStart;

            if ($overlapsShift) {
                throw ValidationException::withMessages([
                    'overtime' => 'Overtime must be before shift start or after shift end.',
                ]);
            }
        }

        $overlap = Overtime::where('timekeeping_id', $timekeeping->id)
            ->where('status', '!=', 'Deleted')
            ->where(function ($q) use ($start, $end) {
                $q->where('startTime', '<', $end)
                ->where('endTime', '>', $start);
            })
            ->exists();

        if ($overlap) {
            //abort(422, 'Overtime overlaps an existing record.');
            throw ValidationException::withMessages([
                'overtime' => 'Overtime overlaps an existing record.',
            ]);
        }

        $hours = round($start->diffInMinutes($end) / 60, 2);

        Overtime::create([
            'timekeeping_id' => $timekeeping->id,
            'empnum' => $timekeeping->empnum,
            'overtimeDate' => $timekeeping->dated ?? now()->toDateString(),
            'startTime' => $start,
            'endTime' => $end,
            'hours' => $hours,
            //'reasons' => $reasons,
            'reasons' => $validated['reasons'], 
            'status' => 'Pending',
            'updated_by' => Auth::user()->empnum,
        ]);

        return redirect()->back()->with('success', 'Overtime saved');
    }

    // Update OT
    public function update(Request $request, Overtime $overtime)
    {
        $validated = $request->validate([
            'startTime' => 'required|date',
            'endTime'   => 'required|date|after:startTime',
            'reasons'   => 'required|string'
        ]);

        // Only pending overtime can be edited
        if ($overtime->status !== 'Pending') {
            throw ValidationException::withMessages([
                'overtime' => 'Cannot edit approved/rejected overtime.',
            ]);
        }

        $timekeeping = Timekeeping::with('corrections')
            ->findOrFail($overtime->timekeeping_id);

        $start = Carbon::parse($validated['startTime']);
        $end   = Carbon::parse($validated['endTime']);
        $baseDate = $start->copy()->startOfDay();

        // Attendance time-in/out (corrected if exists)
        $timeIn = Carbon::parse(
            $timekeeping->correctedTimeIn ?? $timekeeping->timeIn
        )->setDateFrom($baseDate);

        $timeOut = Carbon::parse(
            $timekeeping->correctedTimeOut ?? $timekeeping->timeOut
        )->setDateFrom($baseDate);

        // Night attendance
        if ($timeOut <= $timeIn) {
            $timeOut->addDay();
        }

        // Shift schedule
        $shiftStart = Carbon::parse($timekeeping->shiftStart)->setDateFrom($baseDate);
        $shiftEnd   = Carbon::parse($timekeeping->shiftEnd)->setDateFrom($baseDate);

        // Night shift
        if ($shiftEnd <= $shiftStart) {
            $shiftEnd->addDay();
        }

        // Must be within time in/out
        if ($start < $timeIn || $end > $timeOut) {
            throw ValidationException::withMessages([
                'overtime' => 'Overtime must be within Time In and Time Out.',
            ]);
        }

        // Must be before shift or after shift
        $overlapsShift = $start < $shiftEnd && $end > $shiftStart;

        if ($overlapsShift) {
            throw ValidationException::withMessages([
                'overtime' => 'Overtime must be before shift start or after shift end.',
            ]);
        }

        // Overlap check (exclude current overtime)
        $overlap = Overtime::where('timekeeping_id', $timekeeping->id)
            ->where('id', '!=', $overtime->id) // 👈 IMPORTANT
            ->where('status', '!=', 'Deleted')
            ->where(function ($q) use ($start, $end) {
                $q->where('startTime', '<', $end)
                ->where('endTime', '>', $start);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'overtime' => 'Overtime overlaps an existing record.',
            ]);
        }

        // Recompute hours
        $hours = round($start->diffInMinutes($end) / 60, 2);

        $overtime->update([
            'startTime' => $start,
            'endTime'   => $end,
            'hours'     => $hours,
            'reasons'        => $validated['reasons'], 
            //'reasons'   => $reasons,
        ]);

        return back()->with('success', 'Overtime updated');
    }


    // Delete OT (soft)
    public function destroy(Overtime $overtime)
    {
        if ($overtime->status !== 'Pending') {
            throw ValidationException::withMessages([
                'overtime' => 'Cannot delete approved or rejected overtime.',
            ]);
        }

        $overtime->update([
            'status' => 'Deleted',
        ]);

        return back()->with('success', 'Overtime deleted successfully.');
    }
}
