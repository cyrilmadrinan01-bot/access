<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeContact;
use App\Models\EmployeeAddress;
use App\Models\EmployeeCompensation;
use App\Models\EmployeePersonalInfo;
use App\Services\EmployeeChangeLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Throwable;

class EmployeeController extends Controller
{
    /* =====================================================
    | CREATE VIEW
    ===================================================== */
    public function create()
    {
        return Inertia::render('Employees/Create');
    }

    /* =====================================================
    | CREATE EMPLOYEE
    ===================================================== */
    public function modalStore(Request $request)
    {
        $validated = $request->validate([
            // personal
            'salutation' => 'required|string',
            'prefix' => 'required|string',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'middleName' => 'nullable|string',
            'nickName' => 'nullable|string',
            'gender' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'country_of_birth' => 'required|string',
            'marital_status' => 'required|string',
            'nationality' => 'required|string',
            'religion' => 'required|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',

            // employment
            'company_code' => 'required|string',
            'department_code' => 'required|string',
            'department_name' => 'required|string',
            'shiftCode' => 'required|string',
            'job_code' => 'required|string',
            'job_title' => 'required|string',
            'business_title' => 'required|string',
            'employee_class' => 'required|string',
            'manager_empnum' => 'required|string',
            'hire_date' => 'required|date',
            'status' => 'required|string',

            // contact
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'mobile' => 'nullable|string',
            'contact_person' => 'nullable|string',
            'contact_person_number' => 'nullable|string',

            // address
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'location' => 'nullable|string',

            // compensation
            'salary' => 'nullable|numeric',
            'payGrade' => 'nullable|string',
            'pay_type' => 'nullable|string',
            'factor' => 'nullable|numeric',

            'standard_hours' => 'required|string',
            'employeeType' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            /* =========================
            | EMPLOYEE NUMBER
            ========================= */
            $latestUser = User::orderByDesc('id')->first();

            $newEmpnum = $latestUser
                ? str_pad(((int)$latestUser->empnum + 1), 5, '0', STR_PAD_LEFT)
                : '00001';

            /* =========================
            | EMPLOYEE CORE
            ========================= */
            $employee = Employee::create([
                'empnum' => $newEmpnum,
                'name' => $validated['firstName'] . ' ' . $validated['lastName'],

                'firstName' => $validated['firstName'],
                'lastName' => $validated['lastName'],
                'middleName' => $validated['middleName'] ?? null,
                'nickName' => $validated['nickName'] ?? null,
                'gender' => $validated['gender'] ?? null,

                'deptCode' => $validated['department_code'],
                'deptName' => $validated['department_name'],
                'shiftCode' => $validated['shiftCode'],
                'jobCode' => $validated['job_code'],
                'jobTitle' => $validated['job_title'],
                'businessTitle' => $validated['business_title'],

                'employeeClass' => $validated['employee_class'],
                'companyCode' => $validated['company_code'],
                'manager_empnum' => $validated['manager_empnum'],

                'location' => $validated['location'] ?? 'SPML',
                'country' => $validated['country'] ?? null,

                'standard_hours' => $validated['standard_hours'],
                'employeeType' => $validated['employeeType'],
            ]);

            EmployeeChangeLogger::logChanges(
                $employee->id,
                [],
                $employee->toArray(),
                'employee_core',
                $validated['hire_date']
            );

            /* =========================
            | PERSONAL INFO
            ========================= */
            EmployeePersonalInfo::create([
                'employee_id' => $employee->id,
                'salutation' => $validated['salutation'],
                'prefix' => $validated['prefix'],
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'gender' => $validated['gender'],
                'birth_date' => $validated['birth_date'] ?? null,
                'country_of_birth' => $validated['country_of_birth'],
                'marital_status' => $validated['marital_status'],
                'nationality' => $validated['nationality'],
                'religion' => $validated['religion'],
                'bank_name' => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'effective_start' => $validated['hire_date'],
            ]);

            /* =========================
            | EMPLOYMENT
            ========================= */
            EmployeeEmployment::create([
                'employee_id' => $employee->id,
                'company_code' => $validated['company_code'],
                'department_code' => $validated['department_code'],
                'job_code' => $validated['job_code'],
                'manager_empnum' => $validated['manager_empnum'],
                'status' => $validated['status'],
                'hire_date' => $validated['hire_date'],
                'effective_start' => $validated['hire_date'],
            ]);

            /* =========================
            | CONTACT
            ========================= */
            EmployeeContact::create([
                'employee_id' => $employee->id,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'contact_person_number' => $validated['contact_person_number'] ?? null,
            ]);

            /* =========================
            | ADDRESS
            ========================= */
            EmployeeAddress::create([
                'employee_id' => $employee->id,
                'type' => 'home',
                'address_line1' => $validated['address_line1'] ?? null,
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'] ?? null,
                'effective_start' => $validated['hire_date'],
            ]);

            /* =========================
            | COMPENSATION
            ========================= */
            EmployeeCompensation::create([
                'employee_id' => $employee->id,
                'base_salary' => $validated['salary'] ?? null,
                'pay_grade' => $validated['payGrade'] ?? null,
                'pay_type' => $validated['pay_type'] ?? null,
                'factor' => $validated['factor'] ?? null,
                'effective_start' => $validated['hire_date'],
            ]);

            /* =========================
            | USER CREATION
            ========================= */
            $baseUsername = strtolower(substr($validated['firstName'], 0, 1) . $validated['lastName']);
            $username = $baseUsername;
            $counter = 1;

            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter++;
            }

            User::create([
                'name' => $employee->name,
                'email' => $validated['email'] ?? $newEmpnum . '@company.local',
                'username' => $username,
                'empnum' => $newEmpnum,
                'password' => Hash::make('defaultpassword123'),
                'employeeStatus' => $validated['status'],
            ]);

            DB::commit();

            return back()->with('success', 'Employee created successfully.');

        } catch (Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /* =====================================================
    | UPDATE EMPLOYMENT
    ===================================================== */
    public function updateEmployment(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'company_code' => 'required|string',
            'department_code' => 'required|string',
            'job_code' => 'required|string',
            'manager_empnum' => 'required|string',
            'status' => 'required|string',
            'hire_date' => 'required|date',
            'effective_start' => 'required|date',

            'business_unit' => 'nullable|string',
            'division' => 'nullable|string',
            'cost_center' => 'nullable|string',
            'channel_code' => 'nullable|string',
            'line_code' => 'nullable|string',
            'project' => 'nullable|string',
            'account_code' => 'nullable|string',
            'intercompany' => 'nullable|string',
            'regular_temp' => 'nullable|string',
        ]);

        DB::transaction(function () use ($employee, $data) {

            $current = EmployeeEmployment::where('employee_id', $employee->id)
                ->whereNull('effective_end')
                ->first();

            if ($current) {
                $current->update([
                    'effective_end' => now()->toDateString(),
                    'status' => 'Inactive',
                ]);
            }

            $new = EmployeeEmployment::create([
                'employee_id' => $employee->id,

                'company_code' => $data['company_code'],
                'department_code' => $data['department_code'],
                'job_code' => $data['job_code'],
                'manager_empnum' => $data['manager_empnum'],

                'status' => 'Active',
                'hire_date' => $data['hire_date'],
                'effective_start' => $data['effective_start'],

                'business_unit' => $data['business_unit'] ?? null,
                'division' => $data['division'] ?? null,
                'cost_center' => $data['cost_center'] ?? null,
                'channel_code' => $data['channel_code'] ?? null,
                'line_code' => $data['line_code'] ?? null,
                'project' => $data['project'] ?? null,
                'account_code' => $data['account_code'] ?? null,
                'intercompany' => $data['intercompany'] ?? null,
                'regular_temp' => $data['regular_temp'] ?? null,
            ]);

            $employee->update([
                'companyCode' => $data['company_code'],
                'deptCode' => $data['department_code'],
                'jobCode' => $data['job_code'],
                'manager_empnum' => $data['manager_empnum'],
            ]);

            EmployeeChangeLogger::logChanges(
                $employee->id,
                $current?->toArray() ?? [],
                $new->toArray(),
                'employee_employment',
                $data['effective_start']
            );
        });

        return back()->with('success', 'Employment updated successfully.');
    }

    /* =====================================================
    | UPDATE PERSONAL INFO
    ===================================================== */
    public function updatePersonal(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'effective_start' => 'date',
            'first_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'gender' => 'nullable|in:Male,Female',
            'birth_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:50',
            'account_number' => 'nullable|string|max:50',
            'marital_status' => 'nullable|string|max:50',
            'salutation' => 'nullable|string|max:50',
            'prefix' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            'country_of_birth' => 'nullable|string|max:50',
        ]);

        $old = optional($employee->personalInfos()->first())->toArray() ?? [];

        $employee->personalInfos()->updateOrCreate([], $data);

        EmployeeChangeLogger::logChanges(
            $employee->id,
            $old,
            $data,
            'personal_info'
        );

        return back()->with('success', 'Personal info updated.');
    }

    /* =====================================================
    | CONTACT
    ===================================================== */
    public function updateContact(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'email' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:50',
            'contact_person_number' => 'nullable|string|max:50',
        ]);

        $old = optional($employee->contacts()->first())->toArray() ?? [];

        $employee->contacts()->updateOrCreate([], $data);

        EmployeeChangeLogger::logChanges(
            $employee->id,
            $old,
            $data,
            'contact'
        );

        return back()->with('success', 'Contact updated.');
    }

public function updateGovernmentIds(Request $request, Employee $employee)
{
    $data = $request->validate([
        'tin'        => 'nullable|string|max:50',
        'sss'        => 'nullable|string|max:50',
        'pagibig'    => 'nullable|string|max:50',
        'philhealth' => 'nullable|string|max:50',
    ]);

    $old = $employee->nationalIds()->first()?->toArray() ?? [];

    $new = $employee->nationalIds()->updateOrCreate([], $data);

    // ✅ CHANGE LOG
    EmployeeChangeLogger::logChanges(
        $employee->id,
        $old,
        $new->toArray(),
        'employee_government_ids',
        now()
    );

    return back()->with('success', 'Government IDs updated successfully.');
}

    public function updateAddress(Request $request, Employee $employee)
{
    $data = $request->validate([
        'address_line1'   => 'nullable|string|max:50',
        'address_line2'   => 'nullable|string|max:50',
        'type'            => 'nullable|string|max:50',
        'city'            => 'nullable|string|max:50',
        'province'        => 'nullable|string|max:50',
        'postal_code'     => 'nullable|string|max:50',
        'country'         => 'nullable|string|max:50',
        'effective_start' => 'required|date',
    ]);

    DB::transaction(function () use ($employee, $data) {

        $current = $employee->addressAsOf(Carbon::parse($data['effective_start']));

        if ($current) {
            $current->update([
                'effective_end' => Carbon::parse($data['effective_start'])->subDay(),
            ]);
        }

        $newAddress = $employee->addresses()->create([
            ...$data,
            'effective_end' => null,
        ]);

        // ✅ CHANGE LOG
        EmployeeChangeLogger::logChanges(
            $employee->id,
            $current?->toArray() ?? [],
            $newAddress->toArray(),
            'employee_address',
            $data['effective_start']
        );
    });

    return back()->with('success', 'Address updated successfully.');
}

public function updateCompensation(Request $request, Employee $employee)
{
    $data = $request->validate([
        'base_salary'     => 'nullable|numeric',
        'effective_start' => 'nullable|date',
        'pay_grade'       => 'nullable|string',
        'pay_type'        => 'nullable|in:Daily,Weekly,Semi-Monthly,Monthly',
        'factor'          => 'nullable|numeric',

        'benefits'                => 'nullable|array',
        'benefits.*.id'           => 'nullable|integer',
        'benefits.*.name'         => 'required|string',
        'benefits.*.frequency'    => 'required|string',
        'benefits.*.amount'       => 'nullable|numeric',
        'benefits.*.taxable'      => 'required|boolean',
    ]);

    DB::transaction(function () use ($employee, $data) {

        $oldComp = $employee->compensationRecords()->latest()->first()?->toArray() ?? [];

        $newComp = $employee->compensationRecords()->updateOrCreate([], [
            'effective_start' => $data['effective_start'] ?? null,
            'base_salary'     => $data['base_salary'] ?? null,
            'pay_grade'       => $data['pay_grade'] ?? null,
            'pay_type'        => $data['pay_type'] ?? null,
            'factor'          => $data['factor'] ?? null,
        ]);

        /*
        | BENEFITS SYNC
        */
        $submitted = collect($data['benefits'] ?? []);

        $existingIds = $employee->benefits()->pluck('id');
        $submittedIds = $submitted->pluck('id')->filter();

        // DELETE REMOVED
        $employee->benefits()
            ->whereIn('id', $existingIds->diff($submittedIds))
            ->delete();

        // UPSERT
        foreach ($submitted as $b) {
            if (!empty($b['id'])) {
                $employee->benefits()
                    ->where('id', $b['id'])
                    ->update([
                        'name' => $b['name'],
                        'frequency' => $b['frequency'],
                        'amount' => $b['amount'] ?? 0,
                        'taxable' => $b['taxable'],
                    ]);
            } else {
                $employee->benefits()->create([
                    'name' => $b['name'],
                    'frequency' => $b['frequency'],
                    'amount' => $b['amount'] ?? 0,
                    'taxable' => $b['taxable'],
                    'effective_start' => now(),
                    'effective_end' => '9999-12-31',
                ]);
            }
        }

        // ✅ CHANGE LOG (COMPENSATION)
        EmployeeChangeLogger::logChanges(
            $employee->id,
            $oldComp,
            $newComp->toArray(),
            'employee_compensation',
            $data['effective_start'] ?? now()
        );
    });

    return back()->with('success', 'Compensation updated successfully.');
}

    /* =====================================================
    | MANAGERS + SEARCH
    ===================================================== */
    public function getManagers()
    {
        return Employee::select('empnum', 'firstName', 'lastName')
            ->get()
            ->map(fn ($e) => [
                'empnum' => $e->empnum,
                'name' => $e->firstName . ' ' . $e->lastName,
            ]);
    }

    public function search(Request $request)
    {
        $empnum = $request->query('empnum');

        if (!$empnum) return response()->json(null, 400);

        $employee = Employee::where('empnum', $empnum)
            ->select('id', 'empnum', 'name')
            ->first();

        return $employee ? response()->json($employee) : response()->json(null, 404);
    }
}