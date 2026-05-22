<?php

namespace App\Console\Commands;

use App\Models\Biometric;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBiometricLogs extends Command
{
    protected $signature = 'biometric:sync';
    protected $description = 'Sync biometric logs to HR system';

    public function handle()
    {
        $logs = DB::connection('biometric_db')
            ->table('attendance_logs')
            ->whereDate('timeLog', now()->toDateString()) 
            ->get();

        foreach ($logs as $log) {
            Biometric::firstOrCreate(
                [
                    'empnum'  => $log->empnum,
                    'timeLog' => $log->timeLog,
                    'logType' => $log->logType,
                ],
                [
                    'deviceIp'  => $log->device_ip,
                    'dayName'   => Carbon::parse($log->timeLog)->format('l'),
                    'dated'     => Carbon::parse($log->timeLog)->toDateString(),
                    'processed' => 'No',
                ]
            );
        }

        $this->info('Biometric logs synced.');
    }
}