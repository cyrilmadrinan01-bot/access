<?php

namespace App\Services;

use App\Models\Biometric;
use App\Models\Timekeeping;
use App\Models\UserShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class BiometricProcessor
{
    /**
     * Process a single biometric log.
     */
    public function processLog(array $data)
    {
        DB::beginTransaction();

        try {
            if (isset($data['id'])) {

                $log = Biometric::findOrFail($data['id']);

            } else {

                // 2. If biometric does NOT exist → create it first (API mode)
                $log = Biometric::create([
                    'empnum'    => $data['empnum'],
                    'timeLog'   => $data['timeLog'],
                    'deviceIp'  => $data['deviceIp'],
                    'dayName'   => $data['dayName'],
                    'dated'     => $data['dated'],
                    'processed' => 'No',
                    'logType'   => $data['logType']
                ]);
            }

            $empnum = $log->empnum;
            $logType = $log->logType;
            $timeLog = Carbon::parse($log->timeLog);
            $dated   = Carbon::parse($log->dated);
            $datedString = $dated->toDateString();

            // 1. Shift detection
            $shift = UserShift::select('shift.shiftCode','shift.shiftStart','shift.shiftEnd')
                ->leftJoin('shiftcodes as shift', 'shift.id', '=', 'user_shifts.shiftcode_id')
                ->where('user_shifts.empnum', $empnum)
                ->where('user_shifts.effective_date', '<=', $datedString)
                ->where('user_shifts.end_date', '>=', $datedString)
                ->orderBy('user_shifts.id', 'desc')
                ->first();

            if (!$shift || !isset($shift->shiftStart, $shift->shiftEnd)) {
                throw new Exception("Shift not found or incomplete for $empnum on $datedString");
            }

            $shiftStart = Carbon::parse($datedString . ' ' . $shift->shiftStart);
            $shiftEnd   = Carbon::parse($datedString . ' ' . $shift->shiftEnd);
            if ($shiftEnd->lessThan($shiftStart)) $shiftEnd->addDay();

            // 2. Get or create Timekeeping
            $tk = Timekeeping::firstOrCreate(
                ['empnum' => $empnum, 'dated' => $datedString],
                [
                    'payrollDate' => $datedString,
                    'dayType'     => $this->detectDayType($dated),
                    'shiftCode'   => $shift->shiftCode,
                    'shiftStart'  => $shift->shiftStart,
                    'shiftEnd'    => $shift->shiftEnd,
                    'source'      => 'Biometric'
                ]
            );

            // 3. Decode or initialize segments
            $segments = $tk->segments ?? [];
            if (!is_array($segments)) $segments = json_decode($segments, true) ?? [];

            // 4. Clock IN
            if ($logType === 'Clock In') {
                // Check if the last segment is still open
                $lastSegment = end($segments);
                if ($lastSegment && $lastSegment['out'] === null) {
                    throw new Exception('Cannot clock in again without clocking out from previous shift.');
                }

                // Add new segment
                $segments[] = ['in' => $timeLog->format('Y-m-d H:i:s'), 'out' => null];

                // Update earliest timeIn for the day
                if (!$tk->timeIn || $timeLog->lessThan(Carbon::parse($tk->timeIn))) {
                    $tk->timeIn = $timeLog;
                }
            }


            // 5. Clock OUT
            if ($logType === 'Clock Out') {
                $foundOpen = false;

                // Iterate from the last segment backwards to find the open one
                foreach (array_reverse($segments, true) as $idx => $seg) {
                    if ($seg['out'] === null) {
                        $foundOpen = true;

                        $in = Carbon::parse($seg['in']);
                        $out = $timeLog;

                        // Overnight adjustment
                        if ($out->lessThan($in)) $out->addDay();

                        $segments[$idx]['out'] = $out->format('Y-m-d H:i:s');

                        break;
                    }
                }

                if (!$foundOpen) {
                    throw new Exception('Cannot clock out without a matching clock in.');
                }

                // Update latest timeOut for the day
                if (!$tk->timeOut || $timeLog->greaterThan(Carbon::parse($tk->timeOut))) {
                    $tk->timeOut = $timeLog;
                }
            }


            // 6. Calculations
            $totalWork = 0;
            $totalND = 0;
            foreach ($segments as $seg) {
                if ($seg['in'] && $seg['out']) {
                    $in = Carbon::parse($seg['in']);
                    $out = Carbon::parse($seg['out']);
                    $totalWork += $in->diffInMinutes($out) / 60;
                    $totalND += $this->calculateND($in, $out);
                }
            }

            $late = $tk->timeIn && Carbon::parse($tk->timeIn)->greaterThan($shiftStart)
                ? $shiftStart->diffInMinutes(Carbon::parse($tk->timeIn)) / 60 : 0;

            $undertime = $tk->timeOut && Carbon::parse($tk->timeOut)->lessThan($shiftEnd)
                ? Carbon::parse($tk->timeOut)->diffInMinutes($shiftEnd) / 60 : 0;

            // Regular hours inside shift window minus 1 hour lunch
            $regHours = 0;
            foreach ($segments as $seg) {
                if ($seg['in'] && $seg['out']) {
                    $actualIn  = Carbon::parse($seg['in'])->greaterThan($shiftStart) ? Carbon::parse($seg['in']) : $shiftStart;
                    $actualOut = Carbon::parse($seg['out'])->lessThan($shiftEnd) ? Carbon::parse($seg['out']) : $shiftEnd;
                    if ($actualOut->greaterThan($actualIn)) $regHours += $actualIn->diffInMinutes($actualOut) / 60;
                }
            }
            $regHours = max(0, $regHours - 1);

            // Type code
            $typeCode = $tk->leaveCode ? "On Leave" 
            : (in_array($tk->dayType, ['LH','SH']) ? "Holiday" 
            : ((!$tk->timeIn && !$tk->timeOut) ? "Absent" 
            : (($late > 0 && $undertime > 0) ? "Late/Undertime" 
            : (($late > 0) ? "Late" 
            : (($undertime > 0) ? "Undertime" 
            : "Present")))));

            // Save
            $tk->segments    = $segments;
            $tk->hoursWorked = round($totalWork,2);
            $tk->regHours    = round($regHours,2);
            $tk->nsd         = round($totalND,2);
            $tk->late        = round($late,2);
            $tk->undertime   = round($undertime,2);
            $tk->overtime    = 0;
            $tk->typeCode    = $typeCode;
            $tk->save();

            // Mark biometric log as processed
            $log->processed = 'Yes';
            $log->save();

            DB::commit();

            return $tk;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function detectDayType(Carbon $date)
    {
        $day = $date->format('l');
        if (in_array($day, ['Saturday','Sunday'])) return 'RD';

        $holidays = [
            '2025-12-08' => 'LH',
            '2025-12-25' => 'SH',
        ];

        return $holidays[$date->toDateString()] ?? 'REG';
    }

    private function calculateND(Carbon $start, Carbon $end)
    {
        $ndStart = Carbon::parse($start->toDateString().' 22:00:00');
        $ndEnd = Carbon::parse($start->toDateString().' 06:00:00')->addDay();
        $from = $start->greaterThan($ndStart) ? $start : $ndStart;
        $to = $end->lessThan($ndEnd) ? $end : $ndEnd;
        return ($from < $to) ? $from->diffInMinutes($to)/60 : 0;
    }
}
