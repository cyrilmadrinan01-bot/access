<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
//use App\Jobs\ProcessBiometricLogs;
use App\Models\Biometric;
use App\Services\BiometricProcessor; // import the service
use Exception;

class RunBiometricProcessor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'app:run-biometric-processor';
    protected $signature = 'biometric:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process unprocessed biometric logs';

    /**
     * Execute the console command.
     */
    public function handle(BiometricProcessor $processor)
    {
        $this->info('Processing unprocessed biometric logs...');

        // Fetch all logs that have not been processed
        $logs = Biometric::where('processed', 'No')->get();

        if ($logs->isEmpty()) {
            $this->info('No unprocessed logs found.');
            return 0;
        }

        foreach ($logs as $log) {
            try {
                // Process the log using the service
                $processor->processLog($log);

                $this->info("✅ Processed log for {$log->empnum} at {$log->timeLog}");
            } catch (Exception $e) {
                $this->error("❌ Failed log ID {$log->id}: " . $e->getMessage());
            }
        }

        $this->info('All unprocessed logs have been handled.');

        return 0;
    }
}
