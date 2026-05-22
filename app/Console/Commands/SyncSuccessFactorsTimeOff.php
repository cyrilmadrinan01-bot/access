<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SuccessFactors\TimeOffSyncService;
use Carbon\Carbon;

class SyncSuccessFactorsTimeOff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sf:sync-timeoff 
                            {start : Cutoff start date (Y-m-d)} 
                            {end : Cutoff end date (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync approved SuccessFactors time off into timekeeping table';

    /**
     * Execute the console command.
     */
    public function handle(TimeOffSyncService $service): int
    {
        $start = Carbon::parse($this->argument('start'))->startOfDay();
        $end   = Carbon::parse($this->argument('end'))->endOfDay();

        $this->info("Syncing SuccessFactors time off from {$start} to {$end}");

        $service->syncApprovedTimeOff($start, $end);

        $this->info('✅ Time off sync completed successfully');

        return Command::SUCCESS;
    }
}
