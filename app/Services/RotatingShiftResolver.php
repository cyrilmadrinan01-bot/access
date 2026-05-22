<?php

namespace App\Services;

use App\Models\Shiftcode;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RotatingShiftResolver
{
    protected Collection $shiftcodes;
    protected RotatingShiftPattern $pattern;

    public function __construct(
        Collection $shiftcodes,
        RotatingShiftPattern $pattern
    ) {
        $this->shiftcodes = $shiftcodes;
        $this->pattern = $pattern;
    }

    /**
     * Resolve shiftcode row for employee & date
     */
    public function resolve(Shiftcode $baseShift, string $group, Carbon $date): ?Shiftcode
    {
        if ($baseShift->rotatingShift !== 'Yes') {
            return $baseShift;
        }

        $mode = $this->pattern->resolve($group, $date); // DAY / NIGHT / REST

        if ($mode === 'REST') {
            return null;
        }

        return $this->shiftcodes
            ->where('shiftCode', $baseShift->shiftCode)
            ->where('group', $group)
            ->where('workDay', $mode) // DAY or NIGHT
            ->first();
    }
}
