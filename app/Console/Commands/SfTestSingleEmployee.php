<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SuccessFactors\EmployeeODataService;
use App\Services\SuccessFactors\EmployeeMapper;
use Illuminate\Support\Facades\DB;

class SfTestSingleEmployee extends Command
{
    protected $signature = 'sf:test-employee {personId}';
    protected $description = 'Fetch a single employee from SF and sync to local DB';

    public function handle(EmployeeODataService $service)
    {
        $personId = $this->argument('personId');

        $this->info("Fetching employee personIdExternal = {$personId} ...");

        // ✅ Directly use the service
        $employeeData = $service->fetchByPersonId($personId);

        if (!$employeeData) {
            $this->error("Employee {$personId} not found in SF.");
            //dd('Employee 37056 not found in SF.'); 
            return;
        }
//dd($employeeData);
        // Optional: inspect SF response
        $this->line(json_encode($employeeData, JSON_PRETTY_PRINT));

        // Save to local database inside a transaction
        DB::transaction(function () use ($employeeData) {
            EmployeeMapper::sync($employeeData);
        });

        $this->info("Employee {$personId} successfully synced to local DB.");
    }
}
