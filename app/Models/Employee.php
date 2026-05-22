<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'empnum', 'name','firstName','lastName','nickName','middleName','gender','deptCode','deptName','shiftCode','payGrade','salary','jobCode','jobTitle','businessTitle','employeeClass','companyCode','managerId','location','country','standard_hours','sf_person_id','sf_user_id','employeeType'
    ];

    protected $casts = [
        'deptCode'      => 'string',
        'payGrade'      => 'string',
        'jobCode'       => 'string',
        'jobTitle'      => 'string',
        'businessTitle' => 'string',
        'companyCode'   => 'string',
        'location'      => 'string',
        'country'       => 'string',
        'salary'        => 'float',
    ];

    public function shiftAssignments()
    {
        return $this->hasMany(UserShift::class, 'employee_id');
    }

    public function activeShiftAssignment(Carbon $date)
    {
        return $this->shiftAssignments()
            ->with('shiftCode') // eager load shiftCode
            ->activeForDate($date)
            ->first();
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'managerId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'empnum', 'empnum');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'managerId');
    }

    public function allTeamMembers()
    {
        return $this->teamMembers()->with('allTeamMembers');
    }

    public function compensation()
    {
        return $this->hasOne(Compensation::class);
    }

    public function leaveCredits()
    {
        return $this->hasMany(LeaveCredits::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequests::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class, 'empnum', 'empnum');
    }


    public function getRouteKeyName(): string
    {
        return 'empnum';
    }

    public function employments()
    {
        return $this->hasMany(EmployeeEmployment::class);
    }

    public function compensations()
    {
        return $this->hasMany(EmployeeCompensation::class);
    }

    public function shifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function contacts()
    {
        return $this->hasMany(EmployeeContact::class);
    }


    public function nationalIds()
    {
        return $this->hasOne(EmployeeNationalId::class, 'employee_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(Employee::class, 'managerId')
            ->with('teamMembers');
    }

    /* ==========================
     | Effective Date Resolvers
     ========================== */

    protected function asOf($query, Carbon $date)
    {
        return $query
            ->where('effective_start', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_end')
                  ->orWhere('effective_end', '>=', $date);
            });
    }


    public function employmentAsOf(Carbon $date)
    {
        return $this->asOf($this->employments(), $date)->first();
    }

    public function shiftAsOf(Carbon $date)
    {
        return $this->asOf($this->shifts(), $date)->first();
    }

//    public function contactAsOf(Carbon $date)
//    {
//        return $this->asOf($this->contacts(), $date)->first();
//    }

//    public function nationalIdAsOf(Carbon $date)
//    {
//        return $this->asOf($this->nationalIds(), $date)->first();
//    }

    public function employeeBenefits(): HasMany
    {
        return $this->hasMany(EmployeeBenefit::class);
    }

    public function personalInfos()
    {
        return $this->hasMany(EmployeePersonalInfo::class);
    }

    public function compensationRecords()
    {
        return $this->hasMany(EmployeeCompensation::class);
    }

    public function addresses()
    {
        return $this->hasMany(EmployeeAddress::class);
    } 

    /* ======================
     | AS-OF HELPERS
     ====================== */

    public function personalAsOf($date)
    {
        return $this->personalInfos()
            ->where('effective_start', '<=', $date)
            ->where(fn ($q) =>
                $q->whereNull('effective_end')
                  ->orWhere('effective_end', '>=', $date)
            )
            ->latest('effective_start')
            ->first();
    }

    public function compensationAsOf($date)
    {
        return $this->compensationRecords()
            ->where('effective_start', '<=', $date)
            ->where(fn ($q) =>
                $q->whereNull('effective_end')
                  ->orWhere('effective_end', '>=', $date)
            )
            ->latest('effective_start')
            ->first();
    }

   public function addressAsOf($date)
    {
        // First, try to get the active/current address as of the given date
        $record = $this->addresses()
            ->where('effective_start', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_end')
                ->orWhere('effective_end', '>=', $date);
            })
            ->latest('effective_start')
            ->first();

        // If no active record, get the next future-dated address
        if (!$record) {
            $record = $this->addresses()
                ->where('effective_start', '>', $date)
                ->orderBy('effective_start', 'asc')
                ->first();
        }

        return $record;
    }


    /* ======================
     | FLAT (NON-EFFECTIVE)
     ====================== */

    public function employmentSnapshot()
    {
        return [
            'company_code'      => $this->companyCode,
            'department_name'   => $this->deptName,
            'job_code'          => $this->jobCode,
            'job_title'         => $this->jobTitle,
            'business_title'    => $this->businessTitle,
            'manager_empnum'    => $this->managerId,
            'location'          => $this->location,
            'standard_hours'    => $this->standard_hours,
            'status'            => $this->status,
            'hire_date'         => $this->hire_date,
            'termination_date'  => $this->termination_date,
        ];
    }

    public function shiftSnapshot(?Carbon $date = null): array
    {
        $date ??= now();
        $activeShift = $this->activeShiftAssignment($date);

        return [
            'shift_code'  => $activeShift?->shiftCode?->shiftCode ?? $this->shiftCode,
            'shift_start' => $activeShift?->shiftCode?->shiftStart,
            'shift_end'   => $activeShift?->shiftCode?->shiftEnd,
        ];
    }

    public function benefits()
    {
        return $this->hasMany(EmployeeBenefit::class);
    }

    public function benefitsAsOf(Carbon $date)
    {
        return $this->benefits()
            ->where('effective_start', '<=', $date)
            ->where('effective_end', '>=', $date)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'amount',
                'frequency',
                'taxable',
            ]);
    }

    public function latestNationalId()
    {
        return $this->nationalIds()
            ->latest('id') // or latest('created_at') if you prefer
            ->first();
    }

    public function currentEmployment()
    {
        return $this->hasOne(EmployeeEmployment::class)
            ->where('effective_start', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_end')
                ->orWhere('effective_end', '>=', now());
            })
            ->latest('effective_start');
    }

}
