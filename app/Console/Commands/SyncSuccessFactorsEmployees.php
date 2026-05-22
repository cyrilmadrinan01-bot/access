<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SuccessFactors\EmployeeODataService;
use App\Services\SuccessFactors\EmployeeMapper;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSuccessFactorsEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example:
     * php artisan sf:sync-employees 37056
     */
    //protected $signature = 'sf:sync-employees {personId?}';
    protected $signature = 'sf:sync-employees';
    protected $description = 'Sync employees from SuccessFactors (delta, active + recent terminated)';

    //protected $description = 'Sync employees from SuccessFactors';

    public function handle(EmployeeODataService $service)
    {
        $this->info('Starting SuccessFactors sync...');

        try {
            $employees = $service->fetchDeltaEmployees();

            $count = 0;

            foreach ($employees as $sfEmployee) {
                EmployeeMapper::sync($sfEmployee);
                $count++;
            }

            $this->info("✅ Synced {$count} employees");
            Log::info("SF Sync completed", ['count' => $count]);

        } catch (\Throwable $e) {
            Log::error('SF Sync failed', [
                'error' => $e->getMessage(),
            ]);

            $this->error('❌ Sync failed: ' . $e->getMessage());
        }

        return 0;
    }

    /*
    public function handle(EmployeeODataService $service): int
    {
        $personId = $this->argument('personId');

        if (!$personId) {
            $this->error('personId is required for now.');
            return Command::FAILURE;
        }

        $this->info("Starting SuccessFactors sync for personIdExternal={$personId}");

        // ----------------------------
        // Fetch employees
        // ----------------------------
        try {
            $employees = $service->fetchByPersonId($personId);
        } catch (Throwable $e) {
            $this->error('Failed to fetch data from SuccessFactors');
            logger()->error('SF fetch failed', [
                'personIdExternal' => $personId,
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }

        if (empty($employees)) {
            $this->warn("Employee {$personId} not found in SuccessFactors.");
            return Command::SUCCESS;
        }

        $this->info('Employees fetched: ' . count($employees));

        // ----------------------------
        // Sync employees
        // ----------------------------
        collect($employees)->each(function (array $sf) {
            try {
                EmployeeMapper::sync($sf);
            } catch (Throwable $e) {
                logger()->error('SF employee sync failed', [
                    'personIdExternal' => $sf['personIdExternal'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        $this->info('SuccessFactors sync completed successfully.');

        return Command::SUCCESS;
    }
        */
}
