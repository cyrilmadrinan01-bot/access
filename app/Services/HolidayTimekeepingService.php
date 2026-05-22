<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Employee;
use App\Models\Timekeeping;
use Carbon\Carbon;

class HolidayTimekeepingService
{
    public function applyHoliday(Employee $employee, Carbon $date): void
    {
        $holiday = Holiday::whereDate('date', $date)->first();

        if (! $holiday) {
            return;
        }

        $shiftAssignment = $employee->activeShiftAssignment($date);

        if (! $shiftAssignment || ! $shiftAssignment->shiftCode) {
            return;
        }

        $isRestDay = $this->isRestDay($shiftAssignment->shiftCode, $date);

        $dayType = match (true) {
            $holiday->type === 'Legal' && $isRestDay => 'LHRD',
            $holiday->type === 'Special' && $isRestDay => 'SHRD',
            default => 'Holiday',
        };

        Timekeeping::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
            ],
            [
                'day_type' => $dayType,
                'type_code' => 'Holiday',
            ]
        );
    }

    private function isRestDay($shiftCode, Carbon $date): bool
    {
        return in_array(
            $date->format('l'),
            $shiftCode->restDays()
        );
    }
}
