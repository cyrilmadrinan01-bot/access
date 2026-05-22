<?php

namespace App\Jobs;

use Throwable;
use Carbon\Carbon;
use App\Models\Biometric;
use App\Models\Timekeeping;
use App\Models\UserShift;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBiometricLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $logs = Biometric::where('processed', 'No')
            ->where('retry_count', '<', 5)
            ->orderBy('timeLog', 'asc')
            ->get();

        foreach ($logs as $log) {
            DB::beginTransaction();

            try {
                $this->processRecord($log);

                $log->processed  = 'Yes';
                $log->last_error = null;
                $log->save();

                DB::commit();

            } catch (Throwable $e) {

                DB::rollBack();

                $log->retry_count++;
                $log->last_error = $e->getMessage();
                $log->save();

                if ($log->retry_count >= 5) {
                    $this->sendFailureAlert($log, $e);
                }
            }
        }
    }

    private function processRecord(Biometric $log): void
    {
        // your biometric → timekeeping logic
    }

    private function sendFailureAlert(Biometric $log, Throwable $e): void
    {
        $admin = \App\Models\User::where('is_admin', 1)->first();

        if ($admin) {
            $admin->notify(
                new \App\Notifications\BiometricFailedNotification($log, $e)
            );
        }
    }
}
