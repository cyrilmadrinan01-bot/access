<?php

namespace App\Services;

use Carbon\Carbon;

class RotatingShiftPattern
{
    /**
     * 14-day rotation pattern
     */
    public const PATTERN = [
        1  => ['X'=>'DAY',   'Y'=>'NIGHT', 'Z'=>'REST'],
        2  => ['X'=>'DAY',   'Y'=>'NIGHT', 'Z'=>'REST'],
        3  => ['X'=>'REST',  'Y'=>'NIGHT', 'Z'=>'DAY'],
        4  => ['X'=>'REST',  'Y'=>'NIGHT', 'Z'=>'DAY'],
        5  => ['X'=>'REST',  'Y'=>'REST',  'Z'=>'DAY'],
        6  => ['X'=>'NIGHT', 'Y'=>'REST',  'Z'=>'DAY'],
        7  => ['X'=>'NIGHT', 'Y'=>'REST',  'Z'=>'REST'],

        8  => ['X'=>'NIGHT', 'Y'=>'DAY',   'Z'=>'REST'],
        9  => ['X'=>'NIGHT', 'Y'=>'DAY',   'Z'=>'REST'],
        10 => ['X'=>'REST',  'Y'=>'DAY',   'Z'=>'NIGHT'],
        11 => ['X'=>'REST',  'Y'=>'DAY',   'Z'=>'NIGHT'],
        12 => ['X'=>'DAY',   'Y'=>'REST',  'Z'=>'NIGHT'],
        13 => ['X'=>'DAY',   'Y'=>'REST',  'Z'=>'NIGHT'],
        14 => ['X'=>'DAY',   'Y'=>'NIGHT', 'Z'=>'REST'],
    ];

    protected Carbon $anchorDate;

    public function __construct(?Carbon $anchorDate = null)
    {
        // 🔑 This date represents PATTERN DAY #1
        $this->anchorDate = $anchorDate ?? Carbon::parse('2026-01-01');
    }

    /**
     * Get DAY / NIGHT / REST for group on a given date
     */
    public function resolve(string $group, Carbon $date): string
    {
        $daysDiff = $this->anchorDate->diffInDays($date);
        $dayIndex = ($daysDiff % 14) + 1;

        return self::PATTERN[$dayIndex][$group];
    }
}
