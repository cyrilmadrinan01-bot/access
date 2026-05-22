<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeProfileController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();

        $search = $request->input('search');
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');

        $query = Employee::query()
            ->leftJoin('users', 'employees.empnum', '=', 'users.empnum')
            ->select(
                'employees.id',
                'employees.empnum',
                'employees.name',
                'employees.deptName',
                'employees.jobTitle',
                'employees.managerId',
                'users.employeeStatus'
            );

        // Role filtering
        if ($user->hasAnyRole(['super-admin', 'hr'])) {
            //
        } elseif ($user->hasRole('manager')) {
            $query->where('employees.managerId', $user->empnum);
        } else {
            $query->where('employees.empnum', $user->empnum);
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                ->orWhere('employees.empnum', 'like', "%{$search}%")
                ->orWhere('employees.deptName', 'like', "%{$search}%")
                ->orWhere('employees.jobTitle', 'like', "%{$search}%");
            });
        }

        // Sorting protection
        $allowedSorts = ['name', 'empnum', 'deptName', 'jobTitle'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'name';
        }

        $query->orderBy("employees.$sortField", $sortDirection);

        $employees = $query->paginate(20)->withQueryString();

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'filters' => [
                'search' => $search,
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    } 

    public function show(Request $request, Employee $employee)
    {
        $this->authorize('view', $employee);

        $user = $request->user();

        // Super-admin & HR see all employees
        if ($user->hasAnyRole(['super-admin', 'hr'])) {
            $employees = Employee::all();
        }
        // Manager sees their team
        elseif ($user->hasRole('manager')) {
            $employees = Employee::where('managerId', $user->empnum)->get();
        }
        // Employee sees only themselves
        else {
            $employees = Employee::where('empnum', $user->empnum)->get();
        }

        $asOfDate = $request->date ? Carbon::parse($request->date) : now();

        //$employee->load(['shiftAssignments.shiftCode']);
        $employee->load([
            'employments',
            'shiftAssignments.shiftCode'
        ]);

    $currentEmployment = $employee->employmentAsOf($asOfDate);

        $latestNationalId = $employee->nationalIds()->latest('id')->first();

        return Inertia::render('employees/Profile', [
            'employee' => [
                ...$employee->only([
                    'id',
                    'empnum',
                    'name',
                    'firstName',
                    'lastName',
                    'nickName',
                    'middleName',
                    'gender',
                    'deptCode',
                    'deptName',
                    'jobCode',
                    'jobTitle',
                    'businessTitle',
                    'companyCode',
                    'managerId',
                    'location',
                    'country',
                    'salary',
                    'standard_hours',
                    'employeeType',
                    'employeeClass',
                ]),
                'managers' => \App\Models\Employee::select('empnum', 'name')
                    ->orderBy('name')
                    ->get(),

                'personal'      => $employee->personalAsOf($asOfDate),
                //'employment'    => $employee->employmentSnapshot(),
                'employment'    => $employee->employmentAsOf($asOfDate),
                'compensation'  => $employee->compensationAsOf($asOfDate),
                'shift'         => $employee->shiftSnapshot($asOfDate),
                'address'       => $employee->addressAsOf($asOfDate),
                'benefits'      => $employee->benefitsAsOf($asOfDate),
                'contact'       => $employee->contacts()->first(),

                'national_ids' => [
                    'tin'        => $latestNationalId?->tin,
                    'sss'        => $latestNationalId?->sss,
                    'pagibig'    => $latestNationalId?->pagibig,
                    'philhealth' => $latestNationalId?->philhealth,
                ],
            ],
            'asOfDate' => $asOfDate->toDateString(),

            'permissions' => [
                'employee'    => $request->user()?->can('employee.update.employee') ?? false,
                'employment'  => $request->user()?->can('employee.update.employment') ?? false,
                'compensation'=> $request->user()?->can('employee.update.compensation') ?? false,
                'benefits'    => $request->user()?->can('employee.update.benefits') ?? false,
                'personal'    => $request->user()?->can('employee.update.personal') ?? false,
                'contact'     => $request->user()?->can('employee.update.contact') ?? false,
                'address'     => $request->user()?->can('employee.update.address') ?? false,
                'government'  => $request->user()?->can('employee.update.government') ?? false,
            ],
        ]);
    }

}
