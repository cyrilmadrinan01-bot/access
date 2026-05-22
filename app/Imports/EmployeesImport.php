<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeContact;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeNationalId;
use App\Models\EmployeePersonalInfo;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. Create User
        $user = User::updateOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'empnum' => $row['empnum'],
                'username' => $row['username'],
                'employeeStatus' => 'ACTIVE',
                'password' => Hash::make($row['password'] ?? 'password123'),
            ]
        );

        // 2. Create Employee
        $employee = Employee::updateOrCreate(
            ['empnum' => $row['empnum']],
            [
                'name' => $row['name'],
                'firstName' => $row['first_name'],
                'lastName' => $row['last_name'],
                'middleName' => $row['middle_name'] ?? null,
                'deptCode' => $row['dept_code'],
                'deptName' => $row['dept_name'],
                'shiftCode' => $row['shift_code'],
                'payGrade' => $row['pay_grade'],
                'salary' => $row['salary'],
                'jobCode' => $row['job_code'],
                'jobTitle' => $row['job_title'],
                'businessTitle' => $row['business_title'],
                'employeeClass' => $row['employee_class'],
                'companyCode' => $row['company_code'],
                'managerId' => $row['manager_id'],
                'location' => $row['location'],
                'country' => $row['country'],
                'employeeType' => $row['employee_type'],
            ]
        );

        // 3. Personal Info
        EmployeePersonalInfo::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'gender' => $row['gender'],
                'birth_date' => $row['birth_date'],
                'bank_name' => $row['bank_name'],
                'account_number' => $row['account_number'],
                'marital_status' => $row['marital_status'],
                'salutation' => $row['salutation'],
                'prefix' => $row['prefix'],
                'nationality' => $row['nationality'],
                'religion' => $row['religion'],
                'country_of_birth' => $row['country_of_birth'],
                'effective_start' => now(),
            ]
        );

        // 4. Address
        EmployeeAddress::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'type' => 'HOME',
                'address_line1' => $row['address_line1'],
                'city' => $row['city'],
                'province' => $row['province'],
                'postal_code' => $row['postal_code'],
                'country' => $row['country'] ?? 'PH',
                'effective_start' => now(),
            ]
        );

        // 5. Compensation
        EmployeeCompensation::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'base_salary' => $row['salary'],
                'pay_grade' => $row['pay_grade'],
                'pay_type' => $row['pay_type'] ?? 'MONTHLY',
                'effective_start' => now(),
            ]
        );

        // 6. Contact
        EmployeeContact::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'type' => 'PRIMARY',
                'email' => $row['email'],
                'phone' => $row['phone'] ?? null,
                'mobile' => $row['mobile'] ?? null,
            ]
        );

        // 7. Employment
        EmployeeEmployment::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'company_code' => $row['company_code'],
                'department_code' => $row['dept_code'],
                'job_code' => $row['job_code'],
                'manager_empnum' => $row['manager_id'],
                'status' => 'ACTIVE',
                'hire_date' => $row['hire_date'],
                'business_unit' => $row['business_unit'],
                'division' => $row['division'],
                'cost_center' => $row['cost_center'],
                'channel_code' => $row['channel_code'],
                'line_code' => $row['line_code'],
                'project' => $row['project'],
                'account_code' => $row['account_code'],
                'intercompany' => $row['intercompany'],
                'regular_temp' => $row['regular_temp'],
                'effective_start' => now(),
            ]
        );

        // 8. Government IDs
        EmployeeNationalId::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'tin' => $row['tin'],
                'sss' => $row['sss'],
                'pagibig' => $row['pagibig'],
                'philhealth' => $row['philhealth'],
            ]
        );

        return $employee;
    }
}