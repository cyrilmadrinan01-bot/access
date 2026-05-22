<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeTermination;
use App\Models\EmployeeEmployment;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeeTerminationController extends Controller
{
    public function terminate(Request $request, string $empnum)
    {
        $request->validate([
            'termination_date' => ['required', 'date'],
            'termination_reason' => ['required', 'string', 'max:255'],
            'access_termination_date' => ['nullable', 'date'],
        ]);

        $employee = Employee::where('empnum', $empnum)->firstOrFail();

        $terminationDate = Carbon::parse($request->termination_date)->toDateString();
        $today = now()->toDateString();

        EmployeeTermination::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'empnum' => $employee->empnum,
                'employee_name' => $employee->name,
                'termination_date' => $terminationDate,
                'termination_reason' => $request->termination_reason,
                'access_termination_date' => $request->access_termination_date,
            ]
        );

        /**
         * If future dated:
         * keep active until actual date
         */
        if ($terminationDate > $today) {
            EmployeeEmployment::where('employee_id', $employee->id)
                ->whereNull('effective_end')
                ->update([
                    'status' => 'ACTIVE',
                ]);
        } else {
            /**
             * Immediate termination
             */
            EmployeeEmployment::where('employee_id', $employee->id)
                ->whereNull('effective_end')
                ->update([
                    'status' => 'INACTIVE',
                    'termination_date' => $terminationDate,
                    'effective_end' => $terminationDate,
                ]);
        }

        return back()->with('success', 'Termination scheduled successfully.');
    }

    public function rehire(Request $request, string $empnum)
    {
        $employee = Employee::where('empnum', $empnum)->firstOrFail();

        EmployeeTermination::where('employee_id', $employee->id)->delete();

        EmployeeEmployment::where('employee_id', $employee->id)->update([
            'status' => 'ACTIVE',
            'termination_date' => null,
            'effective_end' => null,
        ]);

        return back()->with('success', 'Employee rehired successfully.');
    }
}

//* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
//php artisan employees:process-terminations