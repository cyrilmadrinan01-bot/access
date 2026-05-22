<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Services\BiometricProcessor;

use App\Http\Controllers\Controller;
use App\Models\Biometric;
use App\Models\UserShift;
use App\Models\Timekeeping;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BiometricController extends Controller
{
    public function index()
    {
        //return Inertia::render('biometric/Index');
        return Inertia::render('biometric/Biometrics');
    }

    // GET /api/barcodes/{empnum}
    public function show(string $empnum, string $ip)
    {
        $empnum = trim(str_replace(['"', "'", "\n", "\r", "\t"], '', $empnum));
        $ip = trim($ip);

        $record = User::select(
            'users.empnum',
            'users.name',
            'users.employeeStatus',
            'ud.deviceIp',
            'device.deviceType'
        )
        ->leftJoin('user_device as ud', 'ud.user_empnum', '=', 'users.empnum')
        ->leftjoin('device', 'device.deviceIp', '=', 'ud.deviceIp')
        ->where('users.empnum', $empnum)
        //->where('ud.deviceIp', $ip)
        ->first();

        return response()->json($record);
    }

//37056 37055
    // POST /api/barcodes/scan
/*    public function store(Request $request)
    {
        $request->validate([
            'empnum' => 'required|string',
            'timeLog' => 'required|date_format:Y-m-d H:i:s',
            'deviceIp' => 'required|string',
            'dayName' => 'required|string',
            'dated' => 'required|date_format:Y-m-d',
            'processed' => 'required|string',
            'logType' => 'required|string'
        ]);

        //$now = Carbon::now();

        $log = biometric_log::create([
            'empnum' => $request->empnum,
            'timeLog' => $request->timeLog,
            'deviceIp' => $request->deviceIp,
            'dayName' => $request->dayName,
            'dated' => $request->dated,
            'processed' => $request->processed,
            'logType' => $request->logType
        ]);

        return response()->json([
            'message' => 'Scan saved successfully.',
            'data' => $log
        ]);
    }
*/

public function store(Request $request, BiometricProcessor $processor)
{
    $request->validate([
        'empnum'   => 'required|string',
        'timeLog'  => 'required|date_format:Y-m-d H:i:s',
        'deviceIp' => 'required|string',
        'dayName'  => 'required|string',
        'dated'    => 'required|date_format:Y-m-d',
        'processed'=> 'required|string',
        'logType'  => 'required|in:Clock In,Clock Out',
    ]);

    //$bio = Biometric::create($request->all());

    //$tk = $processor->processLog($request->all());

    try {
        //$bio = Biometric::create($request->all());
        $tk = $processor->processLog($request->all());

        return response()->json([
            'status' => 'success',
            'message'=> 'Biometric processed successfully.',
            'timekeeping' => $tk
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message'=> $e->getMessage()
        ], 422);
    }
}




    private function detectShift(string $empnum, string $date)
    {
        // Temporary default shift (9 hours including unpaid lunch)
        $shiftData = UserShift::select(
            'shift.shiftCode',
            'shift.shiftStart',
            'shift.shiftEnd'
        )
        ->leftJoin('shiftcodes as shift', 'shift.id', '=', 'user_shifts.shiftcode_id')
        ->where('user_shifts.empnum', $empnum)
        ->where('user_shifts.effective_date', '<=', $date)
        ->where('user_shifts.end_date', '>=', $date)
        ->orderBy('user_shifts.id', 'desc')
        ->first();

        return $shiftData;
 
    }

    /* ============================================================
       DAY TYPE DETECTION (Rest day / Holiday)
    ============================================================ */
    private function detectDayType($empnum, Carbon $date)
    {
        $dayName = $date->format('l');

        // REST DAY EXAMPLE
        if ($dayName === 'Saturday' || $dayName === 'Sunday') {
            return 'RD';
        }

        // HOLIDAY CHECK (replace with real table)
        $holidays = [
            '2025-12-08' => 'LH',
            '2025-12-25' => 'SH',
        ];

        if (isset($holidays[$date->toDateString()])) {
            return $holidays[$date->toDateString()];
        }

        return 'REG';
    }

    /* ============================================================
       NIGHT DIFFERENTIAL: Computes ND hours between 10PM–6AM
    ============================================================ */
    private function calculateND(Carbon $start, Carbon $end)
    {
        $ndStart = Carbon::parse($start->toDateString() . ' 22:00:00');
        $ndEnd   = Carbon::parse($start->toDateString() . ' 06:00:00')->addDay();

        // range overlap
        $from = $start->greaterThan($ndStart) ? $start : $ndStart;
        $to   = $end->lessThan($ndEnd) ? $end : $ndEnd;

        if ($from >= $to) return 0;

        return $from->diffInMinutes($to) / 60;
    }

    /* ============================================================
       MAIN BIOMETRIC PROCESSOR
    ============================================================ */
    public function storexx(Request $request)
    {
        try {
        $request->validate([
                'empnum' => 'required|string',
                'timeLog' => 'required|date_format:Y-m-d H:i:s',
                'deviceIp' => 'required|string',
                'dayName' => 'required|string',
                'dated' => 'required|date_format:Y-m-d',
                'processed' => 'required|string',
                'logType'  => 'required|in:Clock In,Clock Out',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        //DB::beginTransaction();
      
            /* -------------------------------------
               1. Save Raw Biometric Log
            ------------------------------------- */
            $bio = Biometric::create([
                'empnum' => $request->empnum,
                'timeLog' => $request->timeLog,
                'deviceIp' => $request->deviceIp,
                'dayName' => $request->dayName,
                'dated' => $request->dated,
                'processed' => $request->processed,
                'logType' => $request->logType
            ]);

        

            $empnum = $request->empnum;
            $logType = $request->logType;
            $timeLog = Carbon::parse($request->timeLog);
            $dated = Carbon::parse($request->dated); // keep as Carbon
            $datedString = $dated->toDateString();   // "2025-12-09"

            /* -------------------------------------
               2. Shift auto-detection
            ------------------------------------- */
            
            $shift = $this->detectShift($empnum, $datedString);

            if (!$shift) {
                throw new \Exception("Shift not found for employee $empnum on date $dated");
            }

            if (!isset($shift->shiftStart) || !isset($shift->shiftEnd)) {
                throw new \Exception("Shift record missing start or end time.");
            }

            $shiftStart = Carbon::parse($dated->format('Y-m-d') . ' ' . $shift->shiftStart);
            $shiftEnd   = Carbon::parse($dated->format('Y-m-d') . ' ' . $shift->shiftEnd);

            if ($shiftEnd->lessThan($shiftStart)) {
                $shiftEnd->addDay();
            }
            
            /* -------------------------------------
               3. Get or Create Timekeeping Row
            ------------------------------------- */
            
            $tk = Timekeeping::where('empnum', $empnum)
                ->where('dated', $dated->toDateString())
                ->first();

            $data = [
                'payrollDate' => $dated->toDateString(),
                'dayType'     => $this->detectDayType($empnum, $dated),
                'shiftCode'   => $shift['shiftCode'],
                'shiftStart'  => $shift['shiftStart'],
                'shiftEnd'    => $shift['shiftEnd'],
                'source'      => 'Biometric'
            ];

            if ($tk) {
                // UPDATE
                $tk->update($data);
            } else {
                // CREATE
                $tk = Timekeeping::create(array_merge([
                    'empnum' => $empnum,
                    'dated'  => $dated->toDateString(),
                ], $data));
            }


            /* -------------------------------------
               4. Handle Segments
            ------------------------------------- */
            
            $segments = $tk->segments ?? [];
            if (!is_array($segments)) {
                $segments = json_decode($segments, true) ?? [];
            }

            /* -------------------------------------
               5. Clock IN logic
            ------------------------------------- */
            
            if ($logType === 'Clock In') {
                // Prevent duplicate IN
                if (count($segments) > 0 && end($segments)['out'] === null) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'You cannot clock in twice without clocking out.'
                    ], 422);
                }

                $segments[] = [
                    'in'  => $timeLog->format('Y-m-d H:i:s'),
                    'out' => null,
                ];

                if (!$tk->timeIn) {
                    $tk->timeIn = $timeLog;
                }
            }

            /* -------------------------------------
               6. Clock OUT logic
            ------------------------------------- */
            
            if ($logType === 'Clock Out') {

                $foundOpen = false;
                foreach ($segments as &$seg) {
                    if ($seg['out'] === null) {
                        $foundOpen = true;

                        $out = $timeLog;

                        // night shift: if OUT < IN → add day
                        if (Carbon::parse($seg['in'])->greaterThan($out)) {
                            $out->addDay();
                        }

                        $seg['out'] = $out->format('Y-m-d H:i:s');
                        break;
                    }
                }

                if (!$foundOpen) {
                    throw new \Exception("Cannot clock out without a previous clock in.");
                }

                $tk->timeOut = $timeLog;
            }

            /* =============================================================
               7. COMPUTATIONS (Regular Hours, OT, Late, Undertime, ND)
            ============================================================= */
            
            $totalWork = 0;
            $totalND   = 0;

            foreach ($segments as $seg) {
                if ($seg['in'] && $seg['out']) {
                    $in  = Carbon::parse($seg['in']);
                    $out = Carbon::parse($seg['out']);

                    $mins = $in->diffInMinutes($out);
                    $totalWork += $mins / 60;

                    $totalND += $this->calculateND($in, $out);
                }
            }

            /* ----------- LATE ----------- */
            
            $late = 0;
            if ($tk->timeIn) {
                $timeIn = Carbon::parse($tk->timeIn);
                if ($timeIn->greaterThan($shiftStart)) {
                    $late = $shiftStart->diffInMinutes($timeIn) / 60;
                }
            }

            /* ----------- UNDERTIME ----------- */
            
            $undertime = 0;
            if ($tk->timeOut) {
                $timeOut = Carbon::parse($tk->timeOut);
                if ($timeOut->lessThan($shiftEnd)) {
                    $undertime = $timeOut->diffInMinutes($shiftEnd) / 60;
                }
            }

            /* ----------- OVERTIME ----------- */
            
            //$overtime = 0;
            //if ($tk->timeOut) {
            //    $timeOut = Carbon::parse($tk->timeOut);
            //    if ($timeOut->greaterThan($shiftEnd)) {
            //        $overtime = $shiftEnd->diffInMinutes($timeOut) / 60;
            //    }
            //}

            /* =============================================================
            8. REGULAR HOURS (hours worked within the shift window)
            ============================================================= */

            $regHours = 0;

            foreach ($segments as $seg) {
                if ($seg['in'] && $seg['out']) {

                    $in  = Carbon::parse($seg['in']);
                    $out = Carbon::parse($seg['out']);

                    // Determine the overlapping portion within shift window
                    $shiftIn  = $shiftStart;
                    $shiftOut = $shiftEnd;

                    $actualIn  = $in->greaterThan($shiftIn) ? $in : $shiftIn;
                    $actualOut = $out->lessThan($shiftOut) ? $out : $shiftOut;

                    if ($actualOut->greaterThan($actualIn)) {
                        $regHours += $actualIn->diffInMinutes($actualOut) / 60;
                    }
                }
            }

            // Deduct 1 hour (meal break)
            $regHours = $regHours - 1;

            // Ensure it never goes below zero
            if ($regHours < 0) {
                $regHours = 0;
            }

            $regHours = round($regHours, 2);

            /* =============================================================
            9. TYPE CODE LOGIC (ABSENT, LATE, UNDERTIME, LATE/UNDERTIME, 
                                ON LEAVE, HOLIDAY)
            ============================================================= */

            $typeCode = "Present";  // default

            // 9.1 — If leave exists
            if ($tk->leaveCode !== null && $tk->leaveCode !== "") {
                $typeCode = "On Leave";
            }
            // 9.2 — Holiday
            else if ($tk->dayType === "LH" || $tk->dayType === "SH") {
                $typeCode = "Holiday";
            }
            // 9.3 — Absent (No IN + No OUT)
            else if (!$tk->timeIn && !$tk->timeOut) {
                $typeCode = "Absent";
            }
            // 9.4 — Combined Late/Undertime
            else if ($late > 0 && $undertime > 0) {
                $typeCode = "Late/Undertime";
            }
            // 9.5 — Late only
            else if ($late > 0) {
                $typeCode = "Late";
            }
            // 9.6 — Undertime only
            else if ($undertime > 0) {
                $typeCode = "Undertime";
            }

            /* =============================================================
            10. Save Final Data
            ============================================================= */

            $tk->segments     = $segments;
            $tk->hoursWorked  = round($totalWork, 2);      // total worked (all segments)
            $tk->regHours     = round($regHours, 2);       // inside shift only
            $tk->nsd          = round($totalND, 2);
            $tk->late         = round($late, 2);
            $tk->undertime    = round($undertime, 2);
            $tk->overtime     = 0;                         // per your requirement
            $tk->typeCode     = $typeCode;

            $tk->save();


            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Biometric processed successfully.',
                'timekeeping' => $tk
            ]);

        } 

}