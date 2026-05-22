<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Services\SuccessFactors\EmployeeMapper;
use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeContact;
use App\Models\EmployeeAddress;
use App\Models\EmployeeCompensation;
use App\Models\EmployeePersonalInfo;
use Illuminate\Support\Facades\Hash;
use Throwable;

class EmployeeController extends Controller
{
    public function create()
    {
        return Inertia::render('Employees/Create'); 
    }

    public function modalStore(Request $request)
    {
        $validated = $request->validate([
            'salutation' => ['required', 'string'],
            'prefix' => ['required', 'string'],
            'firstName' => ['required', 'string'],
            'lastName' => ['required', 'string'],
            'middleName' => ['nullable', 'string'],
            'nickName' => ['nullable', 'string'],
            'gender' => ['nullable', 'string'],
            'birth_date' => ['nullable', 'date'],
            'country_of_birth' => ['required', 'string'],
            'marital_status' => ['required', 'string'],
            'nationality' => ['required', 'string'],
            'religion' => ['required', 'string'],
            'bank_name' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],

            'company_code' => ['required', 'string'],
            'department_code' => ['required', 'string'],
            'department_name' => ['required', 'string'],
            'shiftCode' => ['required', 'string'],
            'job_code' => ['required', 'string'],
            'job_title' => ['required', 'string'],
            'business_title' => ['required', 'string'],
            'employee_class' => ['required', 'string'],
            'manager_empnum' => ['required', 'string'],
            'hire_date' => ['required', 'date'],
            'status' => ['required', 'string'],

            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'mobile' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string'],
            'contact_person_number' => ['nullable', 'string'],

            'address_line1' => ['nullable', 'string'],
            'address_line2' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'province' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],

            'salary' => ['nullable', 'numeric'],
            'payGrade' => ['nullable', 'string'],
            'pay_type' => ['nullable', 'string'],
            'factor' => ['nullable', 'numeric'],
            'standard_hours' => ['required', 'string'],
            'employeeType' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            // 1️⃣ Generate empnum
            $latestUser = User::orderByDesc('id')->first();
            $newEmpnum = $latestUser
                ? str_pad(intval($latestUser->empnum) + 1, 5, '0', STR_PAD_LEFT)
                : '00001';

            // 2️⃣ Save Employee Core Info
            $employee = Employee::create([
                'empnum' => $newEmpnum,
                'name' => $validated['firstName'] . ' ' . $validated['lastName'],
                'firstName' => $validated['firstName'],
                'lastName' => $validated['lastName'],
                'middleName' => $validated['middleName'] ?? null,
                'nickName' => $validated['nickName'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'deptCode' => $validated['department_code'] ?? null,
                'deptName' => $validated['department_name'] ?? null,
                'shiftCode' => $validated['shiftCode'] ?? null,
                'payGrade' => $validated['payGrade'] ?? null,
                'salary' => $validated['salary'] ?? null,
                'jobCode' => $validated['job_code'] ?? null,
                'jobTitle' => $validated['job_title'] ?? null,
                'businessTitle' => $validated['business_title'] ?? null,
                'employeeClass' => $validated['employee_class'] ?? null,
                'companyCode' => $validated['company_code'] ?? null,
                'managerId' => $validated['manager_empnum'] ?? null,
                'location' => $validated['location'] ?? 'SPML',
                'country' => $validated['country'] ?? null,
                'standard_hours' => $validated['standard_hours'] ?? null,
                'employeeType' => $validated['employeeType'] ?? null,
            ]);

            EmployeePersonalInfo::create([
                'employee_id' => $employee->id,
                
                'salutation' => $validated['salutation'],
                'prefix' => $validated['prefix'],
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'gender' => $validated['gender'],
                'effective_start' => $validated['hire_date'],
                'birth_date' => $validated['birth_date'] ?? null,
                'country_of_birth' => $validated['country_of_birth'],
                'marital_status' => $validated['marital_status'],
                'nationality' => $validated['nationality'],
                'religion' => $validated['relegion'],
                'bank_name' => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
            ]);

            // 3️⃣ Employment Info
            EmployeeEmployment::create([
                'employee_id' => $employee->id,
                'company_code' => $validated['company_code'],
                'department_code' => $validated['department_code'],
                'job_code' => $validated['job_code'],
                'manager_empnum' => $validated['manager_empnum'],
                'hire_date' => $validated['hire_date'],
                'status' => $validated['status'],
                'effective_start' => $validated['hire_date'],
            ]);

            // 4️⃣ Contact Info
            EmployeeContact::create([
                'employee_id' => $employee->id,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'type' => 'phone',
                'contact_person' => $validated['contact_person'] ?? null,
                'contact_person_number' => $validated['contact_person_number'] ?? null,
            ]);

            // 5️⃣ Address Info
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

            // 6️⃣ Compensation Info
            EmployeeCompensation::create([
                'employee_id' => $employee->id,
                'base_salary' => $validated['salary'] ?? null,
                'pay_grade' => $validated['payGrade'] ?? null,
                'pay_type' => $validated['pay_type'] ?? null,
                'factor' => $validated['factor'] ?? null,
                'effective_start' => $validated['hire_date'],
            ]);

            // 7️⃣ Generate unique username
            $baseUsername = strtolower(substr($validated['firstName'], 0, 1) . $validated['lastName']);
            $username = $baseUsername;
            $counter = 1;

            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            // 8️⃣ Create User record
            User::create([
                'name' => $validated['firstName'] . ' ' . $validated['lastName'],
                'email' => $validated['email'] ?? $newEmpnum . '@company.local',
                'username' => $username,
                'empnum' => $newEmpnum,
                'password' => Hash::make('defaultpassword123'),
                'employeeStatus' => $validated['status'],
            ]);

            DB::commit();

            return back()->with('success', 'Employee and User created successfully.');

        } catch (Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save employee: ' . $e->getMessage());
        }
    }

    public function updateEmployment(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'company_code'     => 'required|string',
            'department_code'  => 'required|string',
            'job_code'         => 'required|string',
            'manager_empnum'   => 'required|string',
            'status'           => 'required|string',
            'hire_date'        => 'required|date',
            'termination_date' => 'nullable|date',
            'effective_start'  => 'required|date',
            'business_unit'    => 'required|string',
            'division'         => 'required|string',
            'cost_center'      => 'required|string',
            'channel_code'     => 'required|string',
            'line_code'        => 'required|string',
            'project'          => 'required|string',
            'account_code'     => 'required|string',
            'intercompany'     => 'required|string',
            'regular_temp'     => 'required|string',
        ]);

        DB::transaction(function () use ($employee, $data) {

            // 1️⃣ Close previous employment record
            $currentEmployment = $employee->currentEmployment();

            if ($currentEmployment) {
                $currentEmployment->update([
                    'effective_end' => Carbon::parse($data['effective_start'])->subDay(),
                ]);
            }

            // 2️⃣ Create new effective-dated record
            $employee->employments()->create([
                ...$data,
                'effective_start' => $data['effective_start'],
                'effective_end'   => null, // or 9999-12-31 if that's your standard
            ]);

            // 3️⃣ Sync master table
            $employee->update([
                'companyCode'       => $data['company_code'],
                'deptCode'          => $data['department_code'],
                'jobCode'           => $data['job_code'],
                'managerId'         => $data['manager_empnum'],
                'business_unit'    => $data['business_unit'],
                'division'         => $data['division'],
                'cost_center'      => $data['cost_center'],
                'channel_code'     => $data['channel_code'],
                'line_code'        => $data['line_code'],
                'project'          => $data['project'],
                'account_code'     => $data['account_code'],
                'intercompany'     => $data['intercompany'],
                'regular_temp'     => $data['regular_temp'],
            ]);
        });

        return back()->with('success', 'Employment updated successfully.');
    }

    public function updateGovernmentIds(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'tin' => 'nullable|string|max:50',
            'sss' => 'nullable|string|max:50',
            'pagibig' => 'nullable|string|max:50',
            'philhealth' => 'nullable|string|max:50',
        ]);

        $employee->nationalIds()->updateOrCreate([], $data);

        return back()->with('success', 'Government IDs updated.');
    }

    public function updateAddress(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'address_line1'  => 'nullable|string|max:50',
            'address_line2'  => 'nullable|string|max:50',
            'type'           => 'nullable|string|max:50',
            'city'           => 'nullable|string|max:50',
            'province'       => 'nullable|string|max:50',
            'postal_code'    => 'nullable|string|max:50',
            'country'        => 'nullable|string|max:50',
            'effective_start'=> 'required|date',
        ]);

        DB::transaction(function () use ($employee, $data) {

            // 1️⃣ Close current address record
            $currentAddress = $employee->addressAsOf(Carbon::parse($data['effective_start']));

            if ($currentAddress) {
                $currentAddress->update([
                    'effective_end' => Carbon::parse($data['effective_start'])->subDay(),
                ]);
            }

            // 2️⃣ Create new effective-dated address record
            $employee->addresses()->create([
                ...$data,
                'effective_start' => $data['effective_start'],
                'effective_end'   => null, // or '9999-12-31'
            ]);
        });

        return back()->with('success', 'Address updated successfully.');
    }

    public function updateContact(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'email' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:50',
            'contact_person_number' => 'nullable|string|max:50',
        ]);

        $employee->contacts()->updateOrCreate([], $data);

        return back()->with('success', 'Contact information updated.');
    }

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

        $employee->personalInfos()->updateOrCreate([], $data);

        return back()->with('success', 'Contact information updated.');
    }

    public function updateCompensation(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'base_salary' => 'nullable|numeric',
            'effective_start' => 'date',
            'pay_grade' => 'nullable|numeric',
            'pay_type' => 'nullable|in:Daily,Weekly,Semi-Monthly,Monthly',
            'factor' => 'nullable|numeric',

            'benefits'                => 'nullable|array',
            'benefits.*.id'           => 'nullable|exists:employee_benefits,id',
            'benefits.*.name'         => 'required|string',
            'benefits.*.frequency'    => 'required|string',
            'benefits.*.amount'       => 'nullable|numeric',
            'benefits.*.taxable'      => 'required|boolean',
        ]);

        $employee->compensationRecords()->updateOrCreate([], [
            'effective_start' => $data['effective_start'] ?? null,
            'base_salary' => $data['base_salary'] ?? null,
            'pay_grade'   => $data['pay_grade'] ?? null,
            'pay_type'    => $data['pay_type'] ?? null,
            'factor'      => $data['factor'] ?? null,
        ]);

        $submittedBenefits = collect($data['benefits'] ?? []);

        // Existing benefit IDs in database
        $existingIds = $employee->benefits()->pluck('id');

        // IDs sent from frontend
        $submittedIds = $submittedBenefits->pluck('id')->filter();

        /*
        ---------------------------------------------------
        DELETE REMOVED BENEFITS
        ---------------------------------------------------
        */
        $idsToDelete = $existingIds->diff($submittedIds);

        if ($idsToDelete->isNotEmpty()) {
            $employee->benefits()->whereIn('id', $idsToDelete)->delete();
        }

        /*
        ---------------------------------------------------
        CREATE OR UPDATE BENEFITS
        ---------------------------------------------------
        */
        foreach ($submittedBenefits as $benefit) {

            if (!empty($benefit['id'])) {

                // UPDATE EXISTING
                $employee->benefits()
                    ->where('id', $benefit['id'])
                    ->update([
                        'name'      => $benefit['name'],
                        'frequency' => $benefit['frequency'],
                        'amount'    => $benefit['amount'] ?? 0,
                        'taxable'   => $benefit['taxable'],
                    ]);

            } else {

                // CREATE NEW
                $employee->benefits()->create([
                    'name'            => $benefit['name'],
                    'frequency'       => $benefit['frequency'],
                    'amount'          => $benefit['amount'] ?? 0,
                    'taxable'         => $benefit['taxable'],
                    'effective_start' => now(),
                    'effective_end'   => '9999-12-31',
                ]);
            }
        }

        return back()->with('success', 'Compensation and benefits updated successfully.');
    }

    public function getManagers()
    {
        try {
            $employees = Employee::query()
                ->select('id', 'firstName', 'lastName', 'empnum')
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'empnum' => $e->empnum,
                    'name' => $e->firstName . ' ' . $e->lastName,
                ]);

            return response()->json($employees);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
