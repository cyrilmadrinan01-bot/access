<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Timekeeping;
use App\Models\Shiftcode;
use Illuminate\Support\Facades\DB;

class TimekeepingGenerator
{
    protected RotatingShiftResolver $resolver;
    protected RotatingShiftPattern $pattern;

    public function __construct(
        RotatingShiftResolver $resolver,
        RotatingShiftPattern $pattern
    ) {
        $this->resolver = $resolver;
        $this->pattern = $pattern;
    }

    public function generate($cutoff)
    {
        $employees = DB::table('employees')
            ->get(['empnum', 'shiftcode_id', 'group']);

        $holidays = Holiday::get()
            ->keyBy(fn ($h) => $h->date->format('Y-m-d'));

        foreach ($employees as $employee) {

            $baseShift = Shiftcode::find($employee->shiftcode_id);
            if (!$baseShift) continue;

            $period = Carbon::parse($cutoff->cutOffStart)
                ->daysUntil(Carbon::parse($cutoff->cutOffEnd));

            foreach ($period as $date) {

                if (
                    Timekeeping::where('empnum', $employee->empnum)
                        ->whereDate('dated', $date)
                        ->exists()
                ) continue;

                // 🔄 DAY / NIGHT / REST
                $mode = $this->pattern->resolve($employee->group, $date);

                if ($mode === 'REST') {
                    Timekeeping::create([
                        'empnum'      => $employee->empnum,
                        'dated'       => $date->toDateString(),
                        'payrollDate' => $cutoff->payrollDate,
                        'dayType'     => 'RD',
                        'shiftCode'   => $employee->group,
                        'typeCode'    => 'RestDay',
                        'created_at'  => now(),
                    ]);
                    continue;
                }

                $shift = $this->resolver
                    ->resolve($baseShift, $employee->group, $date);

                if (!$shift) continue;

                $holiday = $holidays->get($date->format('Y-m-d'));

                $dayType = $holiday
                    ? ($holiday->type === 'Legal' ? 'LH' : 'SH')
                    : 'REG';

                Timekeeping::create([
                    'empnum'       => $employee->empnum,
                    'dated'        => $date->toDateString(),
                    'payrollDate'  => $cutoff->payrollDate,
                    'dayType'      => $dayType,
                    'shiftCode'    => $employee->group,
                    'shiftcode_id' => $shift->id,
                    'shiftStart'   => $shift->shiftStart,
                    'shiftEnd'     => $shift->shiftEnd,
                    'regHours'     => $shift->regHours,
                    'hoursWorked'  => $shift->hoursWorked,
                    'typeCode'     => 'Absent',
                    'source'       => 'Auto-Generated',
                    'created_at'   => now(),
                ]);
            }
        }
    }
}
