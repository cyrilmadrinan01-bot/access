<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayrollAdjustment;
use App\Models\Timekeeping;
use App\Models\Shiftcode;
use App\Models\PayrollCutOff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimekeepingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $cutoffs = PayrollCutOff::orderByDesc('cutOffStart')->get([
            'id',
            'cutOffStart',
            'cutOffEnd',
            'payrollDate',
            'current',
        ]);

        $selectedCutoff = $request->cutoff_id
            ? PayrollCutOff::find($request->cutoff_id)
            : PayrollCutOff::where('current', 'Yes')->first();

        $timekeeping = Timekeeping::where('empnum', $user->empnum)
            ->when($selectedCutoff, function ($q) use ($selectedCutoff) {
                $q->whereBetween('dated', [
                    $selectedCutoff->cutOffStart,
                    $selectedCutoff->cutOffEnd,
                ]);
            })
            ->with([
                'corrections' => fn ($q) => $q->latest()->with([ 
                    'shiftCodeRelation:id,shiftCode',
                    'reason:id,reasonName',
                ]),
                'overtimes:id,timekeeping_id,hours,status',
                'adjustment' => fn ($q) => $q->latest()->with([
                    'shiftcode:id,shiftCode',
                    'reason:id,reasonName',
                ]),
            ])
            ->orderBy('dated')
            ->get();

        // ✅ Add current payroll date here
        $currentPayrollDate = PayrollCutOff::where('current', 'Yes')
            ->first()?->payrollDate?->toDateString();
//dd($currentPayrollDate);
        return Inertia::render('timekeeping/Index', [
            'timekeeping' => $timekeeping,
            'cutoffs' => $cutoffs,
            'selectedCutoff' => $selectedCutoff?->id,
            'currentPayrollDate' => $currentPayrollDate, // string | null
        ]);
    }

    public function show($cutoff, $empnum)
    {
        $cutoffRecord = PayrollCutOff::findOrFail($cutoff);

        $timekeeping = Timekeeping::with([
            'corrections.shiftCodeRelation',
            'corrections.reason',
            'overtimes',
            'adjustment.shiftcode',
            'adjustment.reason',
        ])
        ->where('empnum', $empnum)
        ->whereBetween('dated', [
            $cutoffRecord->cutOffStart,
            $cutoffRecord->cutOffEnd,
        ])
        ->orderBy('dated')
        ->get();

        if ($timekeeping->isEmpty()) {
            abort(404);
        }

        return Inertia::render('timekeeping/Show', [
            'timekeeping' => $timekeeping,
            'empnum' => $empnum,
            'cutoff' => $cutoffRecord,
        ]);
    }

}
