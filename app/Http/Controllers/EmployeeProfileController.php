<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeProfileController extends Controller
{
    use AuthorizesRequests;

    /**
     * Employee Listing
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $search         = $request->string('search')->toString();
        $sortField      = $request->string('sort', 'name')->toString();
        $sortDirection  = $request->string('direction', 'asc')->toString();

        $allowedSorts = [
            'name',
            'empnum',
            'deptName',
            'jobTitle',
        ];

        $allowedDirections = ['asc', 'desc'];

        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'name';
        }

        if (! in_array($sortDirection, $allowedDirections)) {
            $sortDirection = 'asc';
        }

        $employees = Employee::query()
            ->leftJoin('users', 'employees.empnum', '=', 'users.empnum')

            ->select([
                'employees.id',
                'employees.empnum',
                'employees.name',
                'employees.deptName',
                'employees.jobTitle',
                'employees.manager_empnum',
                'users.employeeStatus',
            ])

            /**
             * ROLE FILTERING
             */
            ->when(
                $user->hasRole('manager'),
                fn ($query) => $query->where(
                    'employees.manager_empnum',
                    $user->empnum
                )
            )

            ->when(
                ! $user->hasAnyRole(['super-admin', 'hr', 'manager']),
                fn ($query) => $query->where(
                    'employees.empnum',
                    $user->empnum
                )
            )

            /**
             * SEARCH
             */
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('employees.name', 'like', "%{$search}%")
                        ->orWhere('employees.empnum', 'like', "%{$search}%")
                        ->orWhere('employees.deptName', 'like', "%{$search}%")
                        ->orWhere('employees.jobTitle', 'like', "%{$search}%");
                });
            })

            /**
             * SORTING
             */
            ->orderBy("employees.{$sortField}", $sortDirection)

            /**
             * PAGINATION
             */
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('employees/Index', [
            'employees' => $employees,

            'filters' => [
                'search'     => $search,
                'sort'       => $sortField,
                'direction'  => $sortDirection,
            ],
        ]);
    }

    /**
     * Employee Profile
     */
    public function show(Request $request, Employee $employee): Response
    {
        $this->authorize('view', $employee);

        $asOfDate = $request->filled('date')
            ? Carbon::parse($request->date)
            : now();

        /**
         * LOAD RELATIONSHIPS
         */
        $employee->load([
            'employments',
            'shiftAssignments.shiftCode',
        ]);

        /**
         * SNAPSHOTS
         */
        $personal      = $employee->personalAsOf($asOfDate);
        $employment    = $employee->employmentAsOf($asOfDate);
        $compensation  = $employee->compensationAsOf($asOfDate);
        $shift         = $employee->shiftSnapshot($asOfDate);
        $address       = $employee->addressAsOf($asOfDate);
        $benefits      = $employee->benefitsAsOf($asOfDate);
        $contact       = $employee->contacts()->first();

        /**
         * GOVERNMENT IDS
         */
        $nationalId = $employee->nationalIds()
            ->latest('id')
            ->first();

        /**
         * MANAGER SEARCH LIST
         * (FOR AUTOCOMPLETE / SEARCH INPUT)
         */
        $managers = Employee::query()
            ->select([
                'empnum',
                'name',
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('employees/Profile', [

            /**
             * EMPLOYEE
             */
            'employee' => [

                ...$employee->only([
                    'id',
                    'empnum',
                    'name',

                    'firstName',
                    'middleName',
                    'lastName',
                    'nickName',

                    'gender',

                    'deptCode',
                    'deptName',

                    'jobCode',
                    'jobTitle',
                    'businessTitle',

                    'companyCode',
                    'manager_empnum',

                    'location',
                    'country',

                    'salary',
                    'standard_hours',

                    'employeeType',
                    'employeeClass',
                ]),

                /**
                 * SNAPSHOT DATA
                 */
                'personal'      => $personal,
                'employment'    => $employment,
                'compensation'  => $compensation,
                'shift'         => $shift,
                'address'       => $address,
                'benefits'      => $benefits,
                'contact'       => $contact,

                /**
                 * GOVERNMENT IDS
                 */
                'national_ids' => [
                    'tin'        => $nationalId?->tin,
                    'sss'        => $nationalId?->sss,
                    'pagibig'    => $nationalId?->pagibig,
                    'philhealth' => $nationalId?->philhealth,
                ],
            ],

            /**
             * AS OF DATE
             */
            'asOfDate' => $asOfDate->toDateString(),

            /**
             * MANAGER SEARCH DATA
             * USED FOR SEARCHABLE INPUT
             */
            'managers' => $managers,

            /**
             * FRONTEND PERMISSIONS
             */
            'permissions' => [
                'employee'      => $request->user()?->can('employee.update.employee') ?? false,
                'employment'    => $request->user()?->can('employee.update.employment') ?? false,
                'compensation'  => $request->user()?->can('employee.update.compensation') ?? false,
                'benefits'      => $request->user()?->can('employee.update.benefits') ?? false,
                'personal'      => $request->user()?->can('employee.update.personal') ?? false,
                'contact'       => $request->user()?->can('employee.update.contact') ?? false,
                'address'       => $request->user()?->can('employee.update.address') ?? false,
                'government'    => $request->user()?->can('employee.update.government') ?? false,
            ],
        ]);
    }
}