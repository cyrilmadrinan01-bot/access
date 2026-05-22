<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Biometric;
use Carbon\Carbon;

class BiometricLogController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'empnum'   => 'required|string',
            'timeLog'  => 'required|date',
            'logType'  => 'required|in:Clock In,Clock Out',
            'deviceIp' => 'required|string',
        ]);

        $time = Carbon::parse($request->timeLog);

        Biometric::create([
            'empnum'      => $request->empnum,
            'timeLog'     => $time,
            'deviceIp'    => $request->deviceIp,
            'dayName'     => $time->format('l'),
            'dated'       => $time->toDateString(),
            'logType'     => $request->logType,
            'processed'   => 'No',
            'retry_count' => 0,
        ]);

        return response()->json([
            'status' => 'success',
        ]);
    }
}