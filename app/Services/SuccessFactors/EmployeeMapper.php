<?php

namespace App\Services\SuccessFactors;

use App\Models\Employee;
use App\Models\EmployeePersonalInfo;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeNationalId;
use App\Models\EmployeeContact;
use App\Models\EmployeeAddress;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeBenefit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class EmployeeMapper
{
    /**
     * Safely convert any value from SuccessFactors into a scalar
     */
    private static function sanitizeValue($v, $default = '')
    {
        if (is_null($v)) return $default;
        if (is_scalar($v)) return $v;
        if (is_array($v) || is_object($v)) {
            return json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        return (string) $v;
    }

    /**
     * Parse SuccessFactors date into Carbon
     */
    private static function parseSFDate($sfDate, $default = null)
    {
        if (!$sfDate) return $default;
        if (is_string($sfDate) && str_starts_with($sfDate, '/Date(')) {
            return Carbon::createFromTimestampMs((int) preg_replace('/[^0-9]/', '', $sfDate));
        }
        return Carbon::parse($sfDate);
    }
    
    private static function parseSFNumber($value): float
    {
        if (is_null($value)) {
            return 0.00;
        }

        // OData object: ['value' => '6472']
        if (is_array($value) && array_key_exists('value', $value)) {
            return is_numeric($value['value']) ? (float) $value['value'] : 0.00;
        }

        // OData object: stdClass->value
        if (is_object($value) && isset($value->value)) {
            return is_numeric($value->value) ? (float) $value->value : 0.00;
        }

        // Normal scalar
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0.00;
    }

    private static function normalizeEmploymentStatus(string $status): string
    {
        $status = strtoupper(trim($status));

        return match ($status) {
            'UNPAID LEAVE',
            'PAID LEAVE',
            'ACTIVE' => 'Active',

            'TERMINATED',
            'INACTIVE' => 'Inactive',

            default => 'Active',
        };
    }

    /**
     * Sync single SF employee into local DB
     */
    public static function sync(array $sfEmployee): Employee
    {

        if (array_key_exists(0, $sfEmployee)) {
            $sfEmployee = $sfEmployee[0];
        }

        return DB::transaction(function () use ($sfEmployee) {

            $allowedBenefitComponents = [
                'RICE',
                'UNIFORM_ALLOWANCE',
                'LAUNDRY_ALLOWANCE',
                'FUEL',
                'CAR',
                'FTE_SALARY',
                'ABP_PCT',
            ];

            $empnum = $sfEmployee['personIdExternal'] ?? null;

            $personalNav   = data_get($sfEmployee, 'personalInfoNav.results.0');
            $employmentNav = data_get($sfEmployee, 'employmentNav.results.0');
            $jobInfoNav    = data_get($employmentNav, 'jobInfoNav.results.0');
            $compInfoNav   = data_get($employmentNav, 'compInfoNav.results.0');
            $userNav       = data_get($employmentNav, 'userNav');
            $emails        = data_get($sfEmployee, 'emailNav.results');
            $phones        = data_get($sfEmployee, 'phoneNav.results');
            $addresses     = data_get($sfEmployee, 'homeAddressNavDEFLT.results');
            $payComps      = data_get($compInfoNav, 'empPayCompRecurringNav.results');

            // Extract first pay component safely
            //$firstPayComp = is_array($payComps) && count($payComps) > 0 ? $payComps[0] : [];
            $salary = 0.0; 
            
            foreach ($payComps as $comp) { 
                if ( ($comp['payComponent'] ?? null) === 'BASE_SALARY' ) { 
                    $baseSalary = collect($payComps) 
                            ->where('payComponent', 'BASE_SALARY') 
                            ->sortByDesc(fn ($c) => self::parseSFDate($c['startDate'])) 
                            ->first(); 
                    $salary = $baseSalary ? (float) $baseSalary['paycompvalue'] : 0.0; 
                    break; 
                } 
            }

            $homeAddress = collect($addresses)
                ->where('addressType', 'home')
                ->filter(fn ($c) => empty($c['endDate']))
                ->sortByDesc(fn ($c) => self::parseSFDate($c['startDate']))
                ->first();

            // Toggle this to true if you want EVERYTHING from SF
            

            $deptName = data_get($jobInfoNav, 'costCenterNav.name', '');
            //$empStatus = data_get($jobInfoNav, 'emplStatusNav.picklistLabels.results.0.label', '');
            $empClass = data_get($jobInfoNav, 'employeeClassNav.picklistLabels.results.0.label', '');
            $empType = data_get($jobInfoNav, 'customString30Nav.picklistLabels.results.0.label','IDL');
            $rawStatus = data_get($jobInfoNav, 'emplStatusNav.picklistLabels.results.0.label','');

            $empStatus = self::normalizeEmploymentStatus($rawStatus);
            //$statusLabel = data_get($employmentNav, 'employmentStatusNav.name', 'Active');
            

            // ----------------------------
            // 2️⃣ Map main employee record
            // ----------------------------
            $employee = Employee::updateOrCreate(
                ['empnum' => $empnum],
                [
                    'name'          => trim(self::sanitizeValue($personalNav['firstName'] ?? '') . ' ' . self::sanitizeValue($personalNav['lastName'] ?? '')),
                    'firstName'     => self::sanitizeValue($personalNav['firstName'] ?? null),
                    'lastName'      => self::sanitizeValue($personalNav['lastName'] ?? null),
                    'nickName'      => self::sanitizeValue($personalNav['preferredName'] ?? null),
                    'middleName'    => self::sanitizeValue($personalNav['middleName'] ?? null),
                    'gender'        => self::sanitizeValue($personalNav['gender'] ?? null),
                    'shiftCode'     => 'APAC',
                    'deptCode'      => self::sanitizeValue($jobInfoNav['costCenter'] ?? null),
                    'deptName'      => self::sanitizeValue($deptName),
                    'payGrade'      => self::sanitizeValue($jobInfoNav['payGrade'] ?? 0),
                    'salary'        => $salary,
                    'jobCode'       => self::sanitizeValue($jobInfoNav['jobCode'] ?? 0),
                    'jobTitle'      => self::sanitizeValue($jobInfoNav['customString26'] ?? ''),
                    'businessTitle' => self::sanitizeValue($jobInfoNav['localJobTitle'] ?? ''),
                    'employeeClass' => self::sanitizeValue($empClass),
                    'employeeType'  => self::sanitizeValue($empType),
                    'companyCode'   => self::sanitizeValue($jobInfoNav['company'] ?? '205'),
                    'managerId'     => self::sanitizeValue($jobInfoNav['managerId'] ?? null),
                    'location'      => self::sanitizeValue($jobInfoNav['location'] ?? null),
                    'country'       => self::sanitizeValue($jobInfoNav['customString5'] ?? null),
                    'standard_hours'=> self::sanitizeValue($jobInfoNav['standardHours'] ?? '0'),
                    'sf_person_id'  => self::sanitizeValue($sfEmployee['personId'] ?? null),
                    'sf_user_id'    => self::sanitizeValue($employmentNav['userId'] ?? null),
                ]
            );

            // ----------------------------
            // 3️⃣ Personal Info
            // ----------------------------
            if (!empty($personalNav)) {
                EmployeePersonalInfo::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'first_name'      => self::sanitizeValue($personalNav['firstName'] ?? ''),
                        'last_name'       => self::sanitizeValue($personalNav['lastName'] ?? ''),
                        'gender'          => self::sanitizeValue($personalNav['gender'] ?? ''),
                        'effective_start' => self::parseSFDate($personalNav['startDate'], now()),
                        'effective_end'   => self::parseSFDate($personalNav['endDate']),
                    ]
                );
            }

            // ----------------------------
            // 4️⃣ Employment Info
            // ----------------------------
            if (!empty($employmentNav)) {
                EmployeeEmployment::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'company_code'   => self::sanitizeValue($jobInfoNav['company'] ?? '205'),
                        'department_code' => self::sanitizeValue($jobInfoNav['costCenter'] ?? null),
                        'job_code'        => self::sanitizeValue($jobInfoNav['jobCode'] ?? ''),
                        'manager_empnum'  => self::sanitizeValue($jobInfoNav['managerId'] ?? ''),
                        'status'          => self::sanitizeValue($empStatus),
                        'hire_date'       => self::parseSFDate($employmentNav['startDate']),
                        'termination_date'=> self::parseSFDate($employmentNav['lastDateWorked']),
                        'effective_start' => self::parseSFDate($employmentNav['startDate'], now()),
                        'effective_end'   => self::parseSFDate($employmentNav['endDate']),
                    ]
                );
            }

            // ----------------------------
            // 5️⃣ National IDs
            // ----------------------------
            /*
            if (!empty($nationalIdNav)) {
                EmployeeNationalId::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'tin'       => self::sanitizeValue($nationalIdNav['taxId'] ?? null),
                        'sss'       => self::sanitizeValue($nationalIdNav['socialSecurityNumber'] ?? null),
                        'pagibig'   => self::sanitizeValue($nationalIdNav['providentFund'] ?? null),
                        'philhealth'=> self::sanitizeValue($nationalIdNav['healthInsurance'] ?? null),
                    ]
                );
            }
            */
            // ----------------------------
            // 6️⃣ Contacts
            // ----------------------------
            foreach ($emails as $email) {
                EmployeeContact::updateOrCreate(
                    ['employee_id' => $employee->id, 'type' => 'email', 'email' => $email['emailAddress'] ?? ''],
                    [
                        'email' => self::sanitizeValue($email['emailAddress'] ?? ''),
                    ]
                );
            }

            foreach ($phones as $phone) {
                EmployeeContact::updateOrCreate(
                    ['employee_id' => $employee->id, 'type' => 'phone', 'phone' => $phone['phoneNumber'] ?? ''],
                    [
                        'phone'  => self::sanitizeValue($phone['phoneNumber'] ?? ''),
                        'mobile' => self::sanitizeValue($phone['mobileNumber'] ?? ''),
                    ]
                );
            }

            // ----------------------------
            // 7️⃣ Addresses (uncomment if needed)
            // ----------------------------
            
            foreach ($addresses as $address) {
                EmployeeAddress::updateOrCreate(
                    ['employee_id' => $employee->id, 'type' => $address['type'] ?? 'home'],
                    [
                        'address_line1' => self::sanitizeValue($address['address1'] ?? ''),
                        'address_line2' => self::sanitizeValue($address['address2'] ?? ''),
                        'city'          => self::sanitizeValue($address['city'] ?? ''),
                        'province'      => self::sanitizeValue($address['region'] ?? ''),
                        'postal_code'   => self::sanitizeValue($address['zipCode'] ?? ''),
                        'country'       => self::sanitizeValue($address['country'] ?? 'PH'),
                        'effective_start' => self::parseSFDate($address['startDate'], now()),
                        'effective_end'   => self::parseSFDate($address['endDate']),
                    ]
                );
            }
            

            // ----------------------------
            // 8️⃣ Compensation
            // ----------------------------
            if (!empty($compInfoNav)) {
                EmployeeCompensation::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'base_salary'     => $salary,
                        'pay_grade'       => self::sanitizeValue($jobInfoNav['payGrade'] ?? 0),
                        'effective_start' => self::parseSFDate($compInfoNav['startDate'], now()),
                        'effective_end'   => self::parseSFDate($compInfoNav['endDate']),
                    ]
                );
            }

            $storeAllPayComponents = false;

            foreach ($payComps as $comp) {

                $payComponent = $comp['payComponent'] ?? null;

                if (!$payComponent || $payComponent === 'BASE_SALARY') {
                    continue;
                }

                if (
                    !$storeAllPayComponents
                    && !in_array($payComponent, $allowedBenefitComponents, true)
                ) {
                    continue;
                }

                $amount = self::parseSFNumber(
                    $comp['paycompvalue']
                    ?? $comp['payCompValue']
                    ?? null
                );

                EmployeeBenefit::updateOrCreate(
                    [
                        'employee_id'     => $employee->id,
                        'name'            => $payComponent,
                        'effective_start' => self::parseSFDate(
                            $comp['startDate'],
                            now()
                        )->toDateString(),
                    ],
                    [
                        'amount'        => $amount,
                        'frequency'     => self::sanitizeValue(
                            $comp['payComponentFrequency']
                            ?? $comp['frequencyCode']
                            ?? 'MONTHLY'
                        ),
                        'taxable'       => in_array($payComponent, ['ABP_PCT', 'FTE_SALARY'], true),
                        'effective_end' => self::parseSFDate($comp['endDate'])?->toDateString(),
                    ]
                );
            }

            $primaryEmail = collect($emails)
                ->pluck('emailAddress')
                ->filter()
                ->first();

            if (!$primaryEmail) {
                $primaryEmail = strtolower($empnum) . '@company.local';
            }

            User::updateOrCreate(
                ['empnum' => $empnum],
                [
                    'name'               => $employee->name,
                    'email'              => $primaryEmail,
                    'username'           => self::sanitizeValue($userNav['username']?? $empnum),
                    'employeeStatus'     => self::sanitizeValue($empStatus),
                    'email_verified_at'  => now(),

                    // Only set password if user does not exist
                    'password' => User::where('empnum', $empnum)->exists()
                        ? User::where('empnum', $empnum)->value('password')
                        : Hash::make($empnum), // default initial password

                    'remember_token' => Str::random(30),
                ]
            );

            if ($empStatus === 'Inactive') {
                User::where('empnum', $empnum)->update([
                    'remember_token' => null,
                ]);
            }

            return $employee;


        });
    }
}
