<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\EffectiveDatedUpdater;
use App\Models\EmployeeEmployment;

class EmploymentController extends Controller
{
    public function store(Request $request, EffectiveDatedUpdater $service)
    {
        $validated = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'company_code'    => 'required|string',
            'department_code' => 'required|string',
            'job_code'        => 'required|string',
            'manager_empnum'  => 'nullable|string',
            'effective_date'  => 'required|date',
            'changed_by'      => 'required|integer', // add this
        ]);

        $service->apply(
            EmployeeEmployment::class,
            $validated,
            Carbon::parse($validated['effective_date']),
            $validated['changed_by'] // pass user ID from request
        );

        return back()->with('success', 'Employment updated successfully.');
    }
}
