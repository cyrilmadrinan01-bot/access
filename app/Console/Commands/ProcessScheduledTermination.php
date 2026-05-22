<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmployeeTermination;
use App\Models\EmployeeEmployment;

class ProcessScheduledTerminations extends Command
{
    protected $signature = 'employees:process-terminations';

    protected $description = 'Process scheduled employee terminations';

    public function handle()
    {
        $today = now()->toDateString();

        $records = EmployeeTermination::whereDate(
            'termination_date',
            '<=',
            $today
        )->get();

        foreach ($records as $row) {
            EmployeeEmployment::where('employee_id', $row->employee_id)
                ->whereNull('effective_end')
                ->update([
                    'status' => 'INACTIVE',
                    'termination_date' => $row->termination_date,
                    'effective_end' => $row->termination_date,
                ]);
        }

        $this->info('Scheduled terminations processed successfully.');
    }
}