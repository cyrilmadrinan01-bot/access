<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Biometric;

class BiometricController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PAGE VIEW
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        return inertia('biometric/Biometrics');
    }

    /*
    |--------------------------------------------------------------------------
    | REALTIME SCAN API
    | POST /api/biometric/scan
    |--------------------------------------------------------------------------
    | Only saves to biometrics table
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'empnum' => 'required|string'
        ]);

        $empnum = trim($request->empnum);

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | FIND EMPLOYEE
            |--------------------------------------------------------------------------
            */
            $user = User::where('empnum', $empnum)->first();

            if (!$user) {
                throw new \Exception('Employee not found.');
            }

            if ($user->employeeStatus !== 'Active') {
                throw new \Exception('Employee is inactive.');
            }

            /*
            |--------------------------------------------------------------------------
            | CURRENT DATE / TIME
            |--------------------------------------------------------------------------
            */
            $now       = Carbon::now();
            $dateToday = $now->format('Y-m-d');
            $timeNow   = $now->format('Y-m-d H:i:s');
            $dayName   = $now->format('l');
            $deviceIp  = $request->ip();

            /*
            |--------------------------------------------------------------------------
            | AUTO DETECT CLOCK IN / CLOCK OUT
            |--------------------------------------------------------------------------
            | Last successful biometric log today
            |--------------------------------------------------------------------------
            */
            $lastLog = Biometric::where('empnum', $empnum)
                ->whereDate('dated', $dateToday)
                ->where('processed', 'Yes')
                ->latest('id')
                ->first();

            if (!$lastLog) {
                $logType = 'Clock In';
                $message = 'Clock In Successful';
            } elseif ($lastLog->logType === 'Clock In') {
                $logType = 'Clock Out';
                $message = 'Clock Out Successful';
            } else {
                $logType = 'Clock In';
                $message = 'Clock In Successful';
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE TO BIOMETRICS TABLE
            |--------------------------------------------------------------------------
            */
            Biometric::create([
                'empnum'      => $empnum,
                'timeLog'     => $timeNow,
                'deviceIp'    => $deviceIp,
                'dayName'     => $dayName,
                'dated'       => $dateToday,
                'processed'   => 'No',
                'logType'     => $logType,
                'retry_count' => 0,
                'last_error'  => null,
            ]);

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */
            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'data'    => [
                    'empnum' => $user->empnum,
                    'name'   => $user->name,
                    'photo'  => $user->photo ?? null,
                    'type'   => $logType,
                    'time'   => $timeNow
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            /*
            |--------------------------------------------------------------------------
            | SAVE FAILED LOG
            |--------------------------------------------------------------------------
            */
            try {
                Biometric::create([
                    'empnum'      => $empnum,
                    'timeLog'     => now(),
                    'deviceIp'    => $request->ip(),
                    'dayName'     => now()->format('l'),
                    'dated'       => now()->format('Y-m-d'),
                    'processed'   => 'No',
                    'logType'     => 'Clock In',
                    'retry_count' => 1,
                    'last_error'  => $e->getMessage(),
                ]);
            } catch (\Exception $x) {
                // ignore logging errors
            }

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
