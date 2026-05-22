<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Shiftcode;

class TimekeepingCalculator
{
    public static function calculate(
        Shiftcode $shift,
        Carbon $timeIn,
        Carbon $timeOut
    ): array {

        $workedMinutes = $timeIn->diffInMinutes($timeOut);
        $hoursWorked   = round($workedMinutes / 60, 2);

        /* ---------- Deduct break if worked >= 6 hours ---------- */
        if ($hoursWorked >= 5) {
            $hoursWorked -= 1; // deduct 1 hour break
        }

        /* ---------- Regular / OT ---------- */
        $regHours = min($hoursWorked, $shift->regHours);
        //$overtime = max($hoursWorked - $shift->hoursWorked, 0);

        /* ---------- Late ---------- */
        $shiftStart = Carbon::parse($timeIn->toDateString().' '.$shift->shiftStart);
        $late = max(0, round($shiftStart->diffInMinutes($timeIn, false) / 60, 2));

        /* ---------- Undertime ---------- */
        $shiftEnd = Carbon::parse($timeOut->toDateString().' '.$shift->shiftEnd);
        $undertime = max(0, round($timeOut->diffInMinutes($shiftEnd, false) / 60, 2));

        /* ---------- Night Diff ---------- */
        $nsd = self::calculateNSD($shift, $timeIn, $timeOut); 

        $typeCode = "Present";  // default

        if ((!$timeIn && !$timeOut) || !$timeIn || !$timeOut) {
            $typeCode = "Absent";
        }
        else if ($late > 0 && $undertime > 0) {
            $typeCode = "Late/Undertime";
        }
        else if ($late > 0) {
            $typeCode = "Late";
        }
        else if ($undertime > 0) {
            $typeCode = "Undertime";
        }

        return compact(
            'regHours',
            'typeCode',
            'late',
            'undertime',
            'nsd',
            'hoursWorked'
        );
    }

    private static function calculateNSD(Shiftcode $shift, Carbon $timeIn, Carbon $timeOut): float
    {
        $ndStart = Carbon::parse($timeIn->toDateString().' '.$shift->ndStart);
        $ndEnd   = Carbon::parse($timeOut->toDateString().' '.$shift->ndEnd);

        if ($ndEnd->lessThan($ndStart)) {
            $ndEnd->addDay();
        }

        $start = $timeIn->max($ndStart);
        $end   = $timeOut->min($ndEnd);

        if ($start >= $end) return 0;

        return round($start->diffInMinutes($end) / 60, 2);
    }
}
