<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeEffectiveController extends Controller
{
    use AuthorizesRequests; 
    
    public function store(Request $request, Employee $employee, string $section)
    {
        $this->authorize("employee.update.$section");

        $validated = $request->validate([
            'effective_date' => 'required|date',
            'data' => 'required|array'
        ]);

        DB::transaction(function () use ($employee, $section, $validated) {

            $relation = match ($section) {
                'employment' => $employee->employments(),
                'compensation' => $employee->compensations(),
                'personal' => $employee->personals(),
                'address' => $employee->addresses(),
                'contact' => $employee->contacts(),
                'government' => $employee->nationalIds(),
                default => abort(404),
            };

            $exists = $relation
                ->where('effective_date', $validated['effective_date'])
                ->exists();

            if ($exists) {
                abort(422, 'Record already exists for that effective date.');
            }

            $relation->create([
                ...$validated['data'],
                'effective_date' => $validated['effective_date']
            ]);
        });

        return back()->with('success', 'Updated successfully.');
    }
}
