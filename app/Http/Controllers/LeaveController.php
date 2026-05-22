<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveType;
use App\Models\Leave;
use App\Models\Holiday;
use App\Models\EmployeeLeaveBalance;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    /**
     * Show the list of leaves for the current user
     */
    public function index()
    {
        $user = Auth::user();

        // Load leaves with leaveType
        $leaves = Leave::where('empnum', $user->empnum)
            ->with('leaveType')
            ->latest()
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'start_date' => Carbon::parse($l->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($l->end_date)->format('Y-m-d'),
                'status' => strtolower($l->status),
                'leave_type_name' => $l->leaveType->name ?? '',
                'days' => $l->days,
                'hours' => $l->hours,
                'reason' => $l->reason,
            ]);

        return Inertia::render('leave/Index', [
            'leaves' => $leaves,
        ]);
    }

    /**
     * Show form for creating a new leave
     */
    public function create()
    {
        $user = Auth::user();

        // Active leave types
        $leaveTypes = LeaveType::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Load leave balances keyed by leave_type_id
        $balances = EmployeeLeaveBalance::where('empnum', $user->empnum)
            ->get()
            ->map(fn ($b) => [
                'leave_type_id' => $b->leave_type_id,
                'balance' => (float) $b->balance,
            ]);

        // Current user leaves (Approved / Pending)
        $userLeaves = Leave::where('empnum', $user->empnum)
            ->whereIn('status', ['Approved', 'Pending'])
            ->with('leaveType')
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'start_date' => Carbon::parse($l->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($l->end_date)->format('Y-m-d'),
                'status' => strtolower($l->status),
                'leave_type_name' => $l->leaveType->name ?? '',
                'days' => $l->days,
                'hours' => $l->hours,
                'reason' => $l->reason,
            ]);

        // Holidays for current year
        $holidays = Holiday::whereYear('date', now()->year)
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->values();

        return Inertia::render('leaves/Create', [
            'leaveTypes' => $leaveTypes,
            'balances'   => $balances->toArray(), // convert to plain object for Vue
            'userLeaves' => $userLeaves,
            'holidays'   => $holidays,
        ]);
    }

    /**
     * Check if leave overlaps
     */
    private function overlaps(Request $request, $ignoreId = null)
    {
        return Leave::where('empnum', Auth::user()->empnum)
            ->where('status', '!=', 'Rejected')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($q) use ($request) {
                      $q->where('start_date', '<=', $request->start_date)
                        ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->exists();
    }

    /**
     * Store a new leave
     */
    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'days' => 'required|numeric|min:0.5',
            'hours' => 'required|numeric|min:1',
        ]);

        $userEmpnum = Auth::user()->empnum;

        // Load leave balance for the selected leave type
        $balance = EmployeeLeaveBalance::where('empnum', $userEmpnum)
            ->where('leave_type_id', $request->leave_type_id)
            ->first();

        if (!$balance) {
            return back()->with('error', 'No leave balance found for the selected leave type.');
        }

        // Check if requested hours exceed available balance
        if ($request->hours > $balance->balance) {
            return back()->with('error', "Requested leave hours ({$request->hours}) exceed your available balance ({$balance->balance}).");
        }

        if ($this->overlaps($request)) {
            return back()->with('error', 'Overlapping leave exists.');
        }

        Leave::create([
            'empnum' => Auth::user()->empnum,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $request->days,
            'hours' => $request->hours,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        return redirect()->route('leaves.create')->with('success', 'Leave successfully submitted.');
    }

    /**
     * Update leave
     */
    public function update(Request $request, Leave $leave)
    {
        abort_if($leave->status === 'Approved', 403);

        if ($this->overlaps($request, $leave->id)) {
            return back()->withErrors([
                'start_date' => 'Overlapping leave exists.'
            ]);
        }

        $leave->update($request->only([
            'leave_type_id','start_date','end_date','days','hours','reason'
        ]));

        return back();
    }

    /**
     * Delete leave
     */
    public function destroy(Leave $leave)
    {
        abort_if($leave->status === 'Approved', 403);

        $leave->delete();

        return back()->with('success', 'Leave deleted.');
    }
}
