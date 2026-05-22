<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\PayrollCutOff;
use App\Models\Holiday;
use App\Models\Shiftcode;
use App\Models\Timekeeping;

class AdminTimekeepingController extends Controller
{
    public function index()
    {
        $cutoffs = PayrollCutOff::orderByDesc('id')
            ->take(12)
            ->get()
            ->map(function ($cutoff) {

                $cutoffArray = $cutoff->toArray();

                $cutoffArray['hasRunTimekeeping'] = Timekeeping::where('payrollDate', $cutoff->payrollDate)
                    ->exists();

                return $cutoffArray;
            });

        return Inertia::render('admin/payrollcutoff/Index', [
            'cutoffs' => $cutoffs,
        ]);
    }

    public function runTimekeepingGeneration_new($cutoffId)
    {
        $cutoff = PayrollCutOff::findOrFail($cutoffId);

        $employees = DB::table('employees')
            //->get(['empnum', 'shiftcode_id', 'group']); // group = X/Y/Z
            ->get(['empnum', 'shiftCode']);

        $holidays = Holiday::get()
            ->keyBy(fn ($h) => $h->date->format('Y-m-d'));

        $shiftcodes = Shiftcode::where('is_active', 1)->get();

        // 🔑 Rotating services
        $pattern  = new \App\Services\RotatingShiftPattern(
            Carbon::parse('2026-01-01') // anchor date
        );

        $resolver = new \App\Services\RotatingShiftResolver(
            $shiftcodes,
            $pattern
        );

        DB::transaction(function () use (
            $cutoff,
            $employees,
            $holidays,
            $resolver,
            $pattern
        ) {

            foreach ($employees as $employee) {

                $baseShift = Shiftcode::find($employee->shiftCode);
                if (!$baseShift) {
                    continue;
                }

                $period = Carbon::parse($cutoff->cutOffStart)
                    ->daysUntil(Carbon::parse($cutoff->cutOffEnd));

                foreach ($period as $date) {

                    // 🚫 Prevent duplicate
                    if (
                        Timekeeping::where('empnum', $employee->empnum)
                            ->whereDate('dated', $date)
                            ->exists()
                    ) {
                        continue;
                    }

                    $dayName = $date->format('l');
                    $holiday = $holidays->get($date->format('Y-m-d'));

                    // 🔄 Resolve rotation mode (DAY / NIGHT / REST)
                    $mode = $pattern->resolve($employee->group, $date);

                    /*
                    |--------------------------------------------------------------------------
                    | REST DAY (rotating)
                    |--------------------------------------------------------------------------
                    */
                    if ($mode === 'REST') {

                        // 🔥 KEEP YOUR HOLIDAY LOGIC
                        if ($holiday) {
                            $dayType = $holiday->type === 'Legal' ? 'LHRD' : 'SHRD';
                            $typeCode = 'Holiday';
                        } else {
                            $dayType = 'RD';
                            $typeCode = 'RestDay';
                        }

                        Timekeeping::create([
                            'empnum'      => $employee->empnum,
                            'dated'       => $date->toDateString(),
                            'payrollDate' => $cutoff->payrollDate,
                            'dayType'     => $dayType,
                            'shiftCode'   => $employee->group, // X / Y / Z
                            'typeCode'    => $typeCode,
                            'created_at'  => now(),
                        ]);

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | WORK DAY (DAY / NIGHT)
                    |--------------------------------------------------------------------------
                    */
                    $shift = $resolver->resolve(
                        $baseShift,
                        $employee->group,
                        $date
                    );

                    if (!$shift) {
                        continue;
                    }

                    $restDays = explode('|', $shift->restDay);

                    // 🔥 EXACT SAME HOLIDAY LOGIC YOU ALREADY HAVE
                    if ($holiday) {

                        if (in_array($dayName, $restDays)) {
                            $dayType = $holiday->type === 'Legal'
                                ? 'LHRD'
                                : 'SHRD';
                        } else {
                            $dayType = $holiday->type === 'Legal'
                                ? 'LH'
                                : 'SH';
                        }

                        $typeCode = 'Holiday';

                    } elseif (in_array($dayName, $restDays)) {

                        $dayType = 'RD';
                        $typeCode = 'RestDay';

                    } else {

                        $dayType = 'REG';
                        $typeCode = 'Absent';
                    }

                    Timekeeping::create([
                        'empnum'       => $employee->empnum,
                        'dated'        => $date->toDateString(),
                        'payrollDate'  => $cutoff->payrollDate,
                        'dayType'      => $dayType,
                        'shiftCode'    => $employee->group, // X/Y/Z display
                        'shiftcode_id' => $shift->id,
                        'typeCode'     => $typeCode,
                        'shiftStart'   => $shift->shiftStart,
                        'shiftEnd'     => $shift->shiftEnd,
                        'regHours'     => $shift->regHours,
                        'hoursWorked'  => $shift->hoursWorked,
                        'created_at'   => now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Timekeeping generated successfully.');
    }


    public function runTimekeepingGeneration($cutoffId)
    {
        $cutoff = PayrollCutOff::findOrFail($cutoffId);

        $employees = DB::table('employees')->pluck('empnum');

        $holidays = Holiday::get()->keyBy(fn ($h) => $h->date->format('Y-m-d'));

        $shiftcodes = Shiftcode::get()->keyBy('shiftCode');

        DB::transaction(function () use (
            $cutoff,
            $employees,
            $holidays,
            $shiftcodes,
        ) {
            foreach ($employees as $empnum) {

                $period = Carbon::parse($cutoff->cutOffStart)
                    ->daysUntil(Carbon::parse($cutoff->cutOffEnd));

                foreach ($period as $date) {

                    // Prevent duplicate
                    $exists = Timekeeping::where('empnum', $empnum)
                        ->whereDate('dated', $date)
                        ->exists();

                    if ($exists) {
                        continue;
                    }
 
                    $dayName = $date->format('l');

                    // DEFAULT SHIFT (can be replaced by employee schedule)
                    $shift = $shiftcodes->first();

                    $restDays = explode('|', $shift->restDay);

                    // Determine Day Type
                    $holiday = $holidays->get($date->format('Y-m-d'));

                    if ($holiday) {
                        // Holiday falls on a rest day
                        if (in_array($dayName, $restDays)) {
                            $dayType = $holiday->type === 'Legal' ? 'LHRD' : 'SHRD';
                            $typeCode = $holiday->type === 'Legal' ? 'Holiday' : 'Holiday';
                        } else {
                            $dayType = $holiday->type === 'Legal' ? 'LH' : 'SH';
                            $typeCode = $holiday->type === 'Legal' ? 'Holiday' : 'Holiday';
                        }
                    } elseif (in_array($dayName, $restDays)) {
                        $dayType = 'RD';
                        $typeCode = 'RestDay';
                    } else {
                        $dayType = 'REG';
                        $typeCode = 'Absent'; // or 'Absent' if that is your default
                    }

                    Timekeeping::create([
                        'empnum'      => $empnum,
                        'dated'       => $date->toDateString(),
                        'payrollDate' => $cutoff->payrollDate,
                        'dayType'     => $dayType,
                        'shiftCode'   => $shift->shiftCode,
                        'typeCode'    => $typeCode,
                        'shiftStart'  => $shift->shiftStart,
                        'shiftEnd'    => $shift->shiftEnd,
                        'created_at'  => now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Timekeeping generated successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cutOffStart' => ['required', 'date'],
            'cutOffEnd'   => ['required', 'date'],
            'payrollDate' => ['required', 'date'],
            'current'     => ['required', 'in:Yes,No'],
            'lockDate'    => ['nullable', 'date'],
            'lockTime'    => ['nullable'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Only one current cutoff
        |--------------------------------------------------------------------------
        */
        if ($validated['current'] === 'Yes') {

            PayrollCutOff::query()
                ->update([
                    'current' => 'No'
                ]);
        }

        PayrollCutOff::create([
            'cutOffStart' => $validated['cutOffStart'],
            'cutOffEnd'   => $validated['cutOffEnd'],
            'payrollDate' => $validated['payrollDate'],
            'current'     => $validated['current'],
            'lockDate'    => $validated['lockDate'] ?? null,
            'lockTime'    => $validated['lockTime'] ?? null,
        ]);

        return back()->with(
            'success',
            'Payroll cutoff created successfully.'
        );
    }
}
