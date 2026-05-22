<?php

namespace App\Jobs;


use App\Services\SuccessFactors\TimeOffSyncService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncTimeOffJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TimeOffSyncService $service)
    {
        // Define cutoff range; e.g., this month
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        $service->syncApprovedTimeOff($start, $end);
    }
}
