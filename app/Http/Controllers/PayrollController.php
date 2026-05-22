<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\PayrollStepsService;
use App\Models\BankFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\{
    Timekeeping,
    Overtime,
    PayrollCutOff,
    OtherIncome,
    TimekeepingProcess,
    EmployeeDeduction,
    Medical,
    PayrollMedical,
    User,
    SssTable,
    SssContribution,
    PagIbigContribution,
    PhilHealthContribution,
    PayrollRegister,
    Employee,
    EmployeeCompensation,
    PayrollProcessStatus,
    PayrollPayslip,
    PayrollAdjustment
};

class PayrollController extends Controller
{
    /* =========================================================
        INDEX
    ========================================================= */
    public function index(Request $request)
    {
        $cutoffId = $request->cutoff;

        $cutoffs = PayrollCutOff::orderByDesc('cutOffStart')->get();

        $timekeepingRows = collect();
        $otherIncomeRows = collect();
        $deductionRows = collect();
        $medicalRows = collect();
        $sssRows = collect();
        $pagibigRows = collect();
        $philhealthRows = collect();
        $payrollRows = collect();
        $bankRows = collect();
        $payslipRows = collect();
        $statuses = collect();

        if ($cutoffId) {
            $timekeepingRows = TimekeepingProcess::where('payroll_cut_offs_id', $cutoffId)->get();
            $otherIncomeRows = OtherIncome::where('cutoff_id', $cutoffId)->get();
            $deductionRows = EmployeeDeduction::where('cutoff_id', $cutoffId)->get();
            $medicalRows = PayrollMedical::where('cutoff_id', $cutoffId)->get();
            $sssRows = SssContribution::where('cutoff_id', $cutoffId)->get();
            $pagibigRows = PagIbigContribution::where('cutoff_id', $cutoffId)->get();
            $philhealthRows = PhilHealthContribution::where('cutoff_id', $cutoffId)->get();
            $payrollRows = PayrollRegister::where('cutoff_id', $cutoffId)->get();
            $bankRows = BankFile::where('cutoff_id', $cutoffId)->get();
            $payslipRows = PayrollPayslip::where('cutoff_id', $cutoffId)->get();

            $statuses = PayrollProcessStatus::where('cutoff_id', $cutoffId)
                ->pluck('status', 'step');
        }

        return Inertia::render('payroll/PayrollProcess', [
            'cutoffs' => $cutoffs,
            'selectedCutoffId' => $cutoffId,

            'timekeepingRows' => $timekeepingRows,
            'otherIncomeRows' => $otherIncomeRows,
            'employeeDeductionRows' => $deductionRows,
            'medicalRows' => $medicalRows,
            'sssRows' => $sssRows,
            'pagibigRows' => $pagibigRows,
            'philhealthRows' => $philhealthRows,
            'payrollRows' => $payrollRows,
            'bankRows' => $bankRows,
            'payrollPayslipRows' => $payslipRows,

            'stepsStatus' => [
                'timekeeping'  => ($statuses['timekeeping'] ?? null) === 'completed',
                'other_income' => ($statuses['other_income'] ?? null) === 'completed',
                'deduction'    => ($statuses['deduction'] ?? null) === 'completed',
                'medical'      => ($statuses['medical'] ?? null) === 'completed',
                'sss'          => ($statuses['sss'] ?? null) === 'completed',
                'pagibig'      => ($statuses['pagibig'] ?? null) === 'completed',
                'philhealth'   => ($statuses['philhealth'] ?? null) === 'completed',
                'payroll'      => ($statuses['payroll'] ?? null) === 'completed',
                'bank'         => ($statuses['bank'] ?? null) === 'completed',
                'payslip'      => ($statuses['payslip'] ?? null) === 'completed',
            ],
        ]);
    }

    /* =========================================================
        TIMEKEEPING
    ========================================================= */
    public function processTimekeeping(string $cutoffId)
{
    $step = 'timekeeping';

    try {

        PayrollStepsService::start($cutoffId, $step);

        $cutoff = PayrollCutOff::findOrFail($cutoffId);

        DB::transaction(function () use ($cutoff, $cutoffId) {

            /*
            |--------------------------------------------------------------------------
            | DELETE OLD PROCESSED DATA
            |--------------------------------------------------------------------------
            */
            TimekeepingProcess::where(
                'payroll_cut_offs_id',
                $cutoffId
            )->delete();

            /*
            |--------------------------------------------------------------------------
            | GET ALL EMPLOYEES WITH TK OR ADJUSTMENTS
            |--------------------------------------------------------------------------
            */
            $empnums = collect()

                ->merge(
                    Timekeeping::whereBetween('dated', [
                        $cutoff->cutOffStart,
                        $cutoff->cutOffEnd
                    ])->pluck('empnum')
                )

                ->merge(
                    PayrollAdjustment::where(
                        'payroll_cut_off_id',
                        $cutoffId
                    )->pluck('empnum')
                )

                ->unique()
                ->values();

            /*
            |--------------------------------------------------------------------------
            | PROCESS PER EMPLOYEE
            |--------------------------------------------------------------------------
            */
            foreach ($empnums as $empnum) {

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE
    |--------------------------------------------------------------------------
    */
    $employee = Employee::where('empnum', $empnum)
        ->select('id', 'empnum', 'payGrade')
        ->first();

    if (!$employee) {
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAN PAYGRADE
    |--------------------------------------------------------------------------
    */
    $payGrade = (int) preg_replace(
        '/[^0-9]/',
        '',
        (string) $employee->payGrade
    );

    $tkRows = Timekeeping::with('shiftcode')
        ->where('empnum', $empnum)
        ->whereBetween('dated', [
            $cutoff->cutOffStart,
            $cutoff->cutOffEnd
        ])
        ->get();

    $adjustments = PayrollAdjustment::with('timekeeping.shiftcode')
        ->where('payroll_cut_off_id', $cutoffId)
        ->where('empnum', $empnum)
        ->get();

    $totals = [

        'reg' => 0,
        'nsd_reg' => 0,

        'overtime_reg' => 0,
        'overtime_rd' => 0,
        'overtime_lh' => 0,
        'overtime_sh' => 0,
        'overtime_lhrd' => 0,
        'overtime_shrd' => 0,

        'overtime_nsd_reg' => 0,
        'overtime_nsd_rd' => 0,
        'overtime_nsd_lh' => 0,
        'overtime_nsd_sh' => 0,
        'overtime_nsd_lhrd' => 0,
        'overtime_nsd_shrd' => 0,

        'late_reg' => 0,
        'undertime' => 0,

        'absent' => 0,

        'adjusted_hours' => 0,
        'adjusted_nsd' => 0,
        'adjusted_ot_hours' => 0,
        'adjusted_ot_nsd' => 0,

        'meal' => 0,
        'adjusted_meal' => 0,
    ];

    /*
    |--------------------------------------------------------------------------
    | ORIGINAL TIMEKEEPING
    |--------------------------------------------------------------------------
    */
    foreach ($tkRows as $tk) {

        $regHours = (float) ($tk->regHours ?? 0);
        $nsd = (float) ($tk->nsd ?? 0);
        $ot = (float) ($tk->overtime ?? 0);

        $totals['reg'] += $regHours;

        $totals['nsd_reg'] += $nsd;

        $totals['late_reg'] +=
            (float) ($tk->late ?? 0);

        $totals['undertime'] +=
            (float) ($tk->undertime ?? 0);

        /*
        |--------------------------------------------------------------------------
        | ABSENT / UNPAID LEAVE
        |--------------------------------------------------------------------------
        | Count as absent when:
        | - typeCode = Absent
        | - OR leaveCode = UL
        |
        | But ignore for paygrade 44 and above
        |--------------------------------------------------------------------------
        */
        $isAbsent =
            strtoupper((string) $tk->typeCode) === 'Absent';

        $isUnpaidLeave =
            strtoupper((string) ($tk->leaveCode ?? '')) === 'UL';

        if (
            ($isAbsent || $isUnpaidLeave) &&
            $payGrade < 44
        ) {

            $totals['absent'] +=
                (float) (
                    $tk->shiftcode->regHours ?? 8
                );
        }

        /*
        |--------------------------------------------------------------------------
        | MEAL
        |--------------------------------------------------------------------------
        */
        if ($ot >= 2) {

            $totals['meal'] += 1;
        }

        /*
        |--------------------------------------------------------------------------
        | OVERTIME CLASSIFICATION
        |--------------------------------------------------------------------------
        */
        switch ($tk->dayType) {

            case 'RESTDAY':

                $totals['overtime_rd'] += $ot;
                $totals['overtime_nsd_rd'] += $nsd;

                break;

            case 'LEGAL HOLIDAY':

                $totals['overtime_lh'] += $ot;
                $totals['overtime_nsd_lh'] += $nsd;

                break;

            case 'SPECIAL HOLIDAY':

                $totals['overtime_sh'] += $ot;
                $totals['overtime_nsd_sh'] += $nsd;

                break;

            case 'LEGAL HOLIDAY RESTDAY':

                $totals['overtime_lhrd'] += $ot;
                $totals['overtime_nsd_lhrd'] += $nsd;

                break;

            case 'SPECIAL HOLIDAY RESTDAY':

                $totals['overtime_shrd'] += $ot;
                $totals['overtime_nsd_shrd'] += $nsd;

                break;

            default:

                $totals['overtime_reg'] += $ot;
                $totals['overtime_nsd_reg'] += $nsd;

                break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAYROLL ADJUSTMENTS
    |--------------------------------------------------------------------------
    */
    foreach ($adjustments as $adj) {

        $adjHours = (float) ($adj->adjusted_hours ?? 0);
        $adjNsd = (float) ($adj->adjusted_nsd ?? 0);
        $adjOt = (float) ($adj->overtime ?? 0);

        /*
        |--------------------------------------------------------------------------
        | TRACK ADJUSTMENTS
        |--------------------------------------------------------------------------
        */
        $totals['adjusted_hours'] += $adjHours;

        $totals['adjusted_nsd'] += $adjNsd;

        $totals['adjusted_ot_hours'] += $adjOt;

        /*
        |--------------------------------------------------------------------------
        | APPLY TO FINAL PAYROLL
        |--------------------------------------------------------------------------
        */
        $totals['reg'] += $adjHours;

        $totals['nsd_reg'] += $adjNsd;

        $totals['overtime_reg'] += $adjOt;

        /*
        |--------------------------------------------------------------------------
        | LATE / UT
        |--------------------------------------------------------------------------
        */
        $totals['late_reg'] +=
            (float) ($adj->late ?? 0);

        $totals['undertime'] +=
            (float) ($adj->undertime ?? 0);

        /*
        |--------------------------------------------------------------------------
        | ADJUSTMENT MEAL
        |--------------------------------------------------------------------------
        */
        if ($adjOt >= 2) {

            $totals['adjusted_meal'] += 1;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */
    TimekeepingProcess::updateOrCreate(
        [
            'payroll_cut_offs_id' => $cutoffId,
            'empnum' => $empnum,
        ],
        array_map(
            fn ($v) => round($v, 2),
            $totals
        )
    );
}
        });

        PayrollStepsService::complete($cutoffId, $step);

        return back()->with(
            'success',
            'Timekeeping processed successfully.'
        );

    } catch (\Exception $e) {

        PayrollStepsService::fail(
            $cutoffId,
            $step,
            $e->getMessage()
        );

        return back()->with(
            'error',
            $e->getMessage()
        );
    }
}

    /* =========================================================
        OTHER INCOME
    ========================================================= */
    public function uploadOtherIncome(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'cutoff_id' => 'required',
            'is_taxable' => 'required|in:0,1',
        ]);

        $cutoffId = $request->cutoff_id;

        try {
            PayrollStepsService::start($cutoffId, 'other_income');

            DB::transaction(function () use ($request, $cutoffId) {

                OtherIncome::where('cutoff_id', $cutoffId)->delete();

                $sheet = IOFactory::load($request->file('file'))
                    ->getActiveSheet()
                    ->toArray();

                foreach ($sheet as $index => $row) {
                    if ($index === 0) continue;

                    OtherIncome::create([
                        'cutoff_id' => $cutoffId,
                        'empnum' => $row[0] ?? null,
                        'empname' => $row[1] ?? null,
                        'income_type' => $row[2] ?? null,
                        'amount' => $row[3] ?? 0,
                        'is_taxable' => $request->is_taxable,
                        'uploaded_at' => now(),
                    ]);
                }
            });

            PayrollStepsService::complete($cutoffId, 'other_income');

            return back()->with('success', 'Other income uploaded');

        } catch (\Exception $e) {
            PayrollStepsService::fail($cutoffId, 'other_income', $e->getMessage());

            return back()->with('error', $e->getMessage());
        }
    }

    /* =========================================================
        DEDUCTION
    ========================================================= */
    public function uploadEmployeeDeduction(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'cutoff_id' => 'required',
        ]);

        $cutoffId = $request->cutoff_id;

        try {
            PayrollStepsService::start($cutoffId, 'deduction');

            DB::transaction(function () use ($request, $cutoffId) {

                EmployeeDeduction::where('cutoff_id', $cutoffId)->delete();

                $sheet = IOFactory::load($request->file('file'))
                    ->getActiveSheet()
                    ->toArray();

                foreach ($sheet as $index => $row) {
                    if ($index === 0) continue;

                    EmployeeDeduction::create([
                        'cutoff_id' => $cutoffId,
                        'empnum' => $row[0] ?? null,
                        'empname' => $row[1] ?? null,
                        'deduction_type' => $row[2] ?? null,
                        'amount' => $row[3] ?? 0,
                        'uploaded_at' => now(),
                    ]);
                }
            });

            PayrollStepsService::complete($cutoffId, 'deduction');

            return back()->with('success', 'Deductions uploaded');

        } catch (\Exception $e) {
            PayrollStepsService::fail($cutoffId, 'deduction', $e->getMessage());

            return back()->with('error', $e->getMessage());
        }
    }

    /* =========================================================
        MEDICAL
    ========================================================= */
    public function processMedical(string $cutoffId)
    {
        try {
            PayrollStepsService::start($cutoffId, 'medical');

            DB::transaction(function () use ($cutoffId) {

                PayrollMedical::where('cutoff_id', $cutoffId)->delete();

                $records = Medical::where('status', 'APPROVED')
                    ->where('processed', 'No')
                    ->get()
                    ->groupBy('empnum');

                foreach ($records as $empnum => $rows) {
                    PayrollMedical::create([
                        'cutoff_id' => $cutoffId,
                        'empnum' => $empnum,
                        'empname' => $rows->first()->empname,
                        'total_amount' => $rows->sum('amount'),
                        'processed_at' => now(),
                    ]);
                }
            });

            PayrollStepsService::complete($cutoffId, 'medical');

            return back()->with('success', 'Medical processed');

        } catch (\Exception $e) {
            PayrollStepsService::fail($cutoffId, 'medical', $e->getMessage());

            return back()->with('error', $e->getMessage());
        }
    }

    /* =========================================================
        SSS
    ========================================================= */
    public function processSSS(Request $request, string $cutoffId)
    {
        $step = 'sss';

        $request->validate([
            'year' => 'required|digits:4',
            'month' => 'required|digits_between:1,2',
        ]);

        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);

        try {
            PayrollStepsService::start($cutoffId, $step);

            DB::transaction(function () use ($cutoffId, $year, $month) {

                SssContribution::where('cutoff_id', $cutoffId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->delete();

                $employees = User::with('employee.nationalIds')
                    ->where('employeeStatus', 'Active')
                    ->get();

                foreach ($employees as $user) {

                    $employee = $user->employee;
                    if (!$employee) continue;

                    $salary = $employee->salary ?? 0;

                    $sssBracket = SssTable::where('salary_min', '<=', $salary)
                        ->where('salary_max', '>=', $salary)
                        ->first();

                    if (!$sssBracket) continue;

                    $sssNumber = optional(optional($employee->nationalIds)->first())->sss;
                    if (!$sssNumber) continue;

                    SssContribution::create([
                        'cutoff_id' => $cutoffId,
                        'empnum' => $user->empnum,
                        'empname' => $user->name,
                        'sss_number' => $sssNumber,
                        'year' => $year,
                        'month' => $month,
                        'employee' => $sssBracket->employee_total,
                        'employer' => $sssBracket->employer_regular + $sssBracket->employer_mpf,
                        'ec' => $sssBracket->employer_ec,
                        'processed_at' => now(),
                    ]);
                }
            });

            PayrollStepsService::complete($cutoffId, $step);

            return back()->with('success', 'SSS Contribution was processed');

        } catch (\Exception $e) {
            PayrollStepsService::fail($cutoffId, $step, $e->getMessage());

            return back()->with('error', $e->getMessage());
        }
    }

    /* =========================================================
        PAGIBIG
    ========================================================= */
    public function processPagibig(Request $request, string $cutoffId)
    {
        $step = 'pagibig';

        $request->validate([
            'year' => 'required|digits:4',
            'month' => 'required|digits_between:1,2'
        ]);

        $cutoff = PayrollCutOff::findOrFail($cutoffId);

        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($year, $month, $cutoff) {

            // Delete previous Pag-IBIG for same period + cutoff
            PagIbigContribution::where('year', $year)
                ->where('month', $month)
                ->where('cutoff_id', $cutoff->id)
                ->delete();

            $employees = User::with('employee.nationalIds')
                ->where('employeeStatus', 'Active')
                ->get();

            foreach ($employees as $user) {

                $employee = $user->employee;
                if (!$employee) continue;

                // 🔹 Sample Pag-IBIG computation (standard PH rule)
                $employeeShare = '200';
                $employerShare = '200';

                $pagibigNumber = optional(optional($employee->nationalIds)->first())->pagibig;
                if (!$pagibigNumber) continue;

                PagIbigContribution::create([
                    'cutoff_id'     => $cutoff->id,
                    'empnum'        => $user->empnum,
                    'empname'       => $user->name,
                    'pagibig_number'=> $pagibigNumber,
                    'year'          => $year,
                    'month'         => $month,
                    'employee'      => round($employeeShare, 2),
                    'employer'      => round($employerShare, 2),
                    'processed_at'  => now(),
                ]);
            }
        });

            PayrollStepsService::complete($cutoffId, $step);

            return back()->with('success', 'Pag-IBIG Contribution was processed');

    }

    public function processPhilhealth(Request $request, string $cutoffId)
    {
        $step = 'philhealth';

        $request->validate([
            'year' => 'required|digits:4',
            'month' => 'required|digits_between:1,2'
        ]);

        $cutoff = PayrollCutOff::findOrFail($cutoffId);

        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($year, $month, $cutoff) {

            // Delete previous Pag-IBIG for same period + cutoff
            PhilHealthContribution::where('year', $year)
                ->where('month', $month)
                ->where('cutoff_id', $cutoff->id)
                ->delete();

            $employees = User::with('employee.nationalIds')
                ->where('employeeStatus', 'Active')
                ->get();

            foreach ($employees as $user) {

                $employee = $user->employee;
                if (!$employee) continue;

                $basicSalary = $employee->salary ?? 0;

                if ($basicSalary <= 10000) {
                    $total = 500;
                } elseif ($basicSalary >= 100000) {
                    $total = 5000;
                } else {
                    $total = $basicSalary * 0.05;
                }

                $employeeShare = $total / 2;
                $employerShare = $total / 2;

                $philhealthNumber = optional(optional($employee->nationalIds)->first())->philhealth;
                if (!$philhealthNumber) continue;

                PhilHealthContribution::create([
                    'cutoff_id'     => $cutoff->id,
                    'empnum'        => $user->empnum,
                    'empname'       => $user->name,
                    'philhealth_number'=> $philhealthNumber,
                    'year'          => $year,
                    'month'         => $month,
                    'employee'      => round($employeeShare, 2),
                    'employer'      => round($employerShare, 2),
                    'processed_at'  => now(),
                ]);
            }
        });

            PayrollStepsService::complete($cutoffId, $step);

            return back()->with('success', 'PhilHealth Contribution was processed');

    }

public function processPayroll(Request $request, int $cutoffId)
{
    //$taxationType = $request->input('payrollType', 'Semi-Monthly');

    DB::transaction(function () use ($cutoffId) {

        $cutoff = PayrollCutOff::findOrFail($cutoffId);
        $payday = Carbon::parse($cutoff->payrollDate)->day;
        $month = Carbon::parse($cutoff->payrollDate)->month;
        $year = Carbon::parse($cutoff->payrollDate)->year;

        $taxMode = ($month <= 6)
            ? 'Semi-Monthly'
            : 'Annual';

        /*
        |--------------------------------------------------------------------------
        | 1. Main Query (single fetch / optimized)
        |--------------------------------------------------------------------------
        */
        $employees = DB::table('timekeeping_processes as tk')
            ->join('employees as e', 'e.empnum', '=', 'tk.empnum')
            ->join('employee_compensations as c', 'c.employee_id', '=', 'e.id')
            ->leftJoin('employee_personal_infos as p', 'p.employee_id', '=', 'e.id')

            ->leftJoin('sss_contributions as sss', function ($join) {
                $join->on('sss.empnum', '=', 'e.empnum');
            })

            ->leftJoin('pag_ibig_contributions as hdmf', function ($join) {
                $join->on('hdmf.empnum', '=', 'e.empnum');
            })

            ->leftJoin('philhealth_contributions as phil', function ($join) {
                $join->on('phil.empnum', '=', 'e.empnum');
            })

            ->where('tk.payroll_cut_offs_id', $cutoffId)

            ->select([
                'e.id as employee_id',
                'e.empnum',
                'e.name as empname',
                'e.standard_hours',

                'c.base_salary',
                'c.pay_type',
                'c.factor',

                'p.account_number',

                'tk.*',

                DB::raw('COALESCE(sss.employee,0) as sss_ee'),
                DB::raw('COALESCE(sss.employer,0) as sss_er'),
                DB::raw('COALESCE(sss.ec,0) as sss_ec'),

                DB::raw('COALESCE(hdmf.employee,0) as hdmf_ee'),
                DB::raw('COALESCE(hdmf.employer,0) as hdmf_er'),

                DB::raw('COALESCE(phil.employee,0) as phil_ee'),
                DB::raw('COALESCE(phil.employer,0) as phil_er'),
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. Preload Benefits / Income / Deductions
        |--------------------------------------------------------------------------
        */
        $benefits = DB::table('employee_benefits')
            ->where('payday', $payday)
            ->get()
            ->groupBy('employee_id');

        $otherIncome = DB::table('other_incomes')
            ->where('cutoff_id', $cutoffId)
            ->get()
            ->groupBy('empnum');

        $deductions = DB::table('employee_deductions')
            ->where('cutoff_id', $cutoffId)
            ->get()
            ->groupBy('empnum');

        /*
        |--------------------------------------------------------------------------
        | 3. Process each employee
        |--------------------------------------------------------------------------
        */
        foreach ($employees as $row) {

            /*
            |--------------------------------------------------------------------------
            | Reset Values per employee (IMPORTANT)
            |--------------------------------------------------------------------------
            */
            $busMarshallAllowance = 0;
            $monthlyHomeSubsidy = 0;
            $cashGift = 0;
            $ltiCashAwards = 0;
            $retentionBonus = 0;

            $sssLoan = 0;
            $coopLoan = 0;
            $hdmfLoan = 0;
            $employeeSavings = 0;
            $taxAdjustment = 0;

            $uniformAllowance = 0;
            $gasAllowance = 0;
            $riceAllowance = 0;
            $laundryAllowance = 0;

            /*
            |--------------------------------------------------------------------------
            | Base Rates
            |--------------------------------------------------------------------------
            */
            $annualSalary = (float) $row->base_salary;
            $factor       = max((float) $row->factor, 1);

            $monthlyRate = $annualSalary / 12;
            $hourlyRate  = $monthlyRate / $factor;
            $basicPay    = $monthlyRate / 2;

            /*
            |--------------------------------------------------------------------------
            | Hours
            |--------------------------------------------------------------------------
            */
            $lateHours      = (float) ($row->late_reg ?? 0);
            $undertimeHours = (float) ($row->undertime ?? 0);
            $absentHours    = (float) ($row->absent ?? 0);

            $nsdHours   = (float) ($row->nsd_reg ?? 0);
            $otHours    = (float) ($row->overtime_reg ?? 0);
            $nsdOtHours = (float) ($row->overtime_nsd_reg ?? 0);

            /*
            |--------------------------------------------------------------------------
            | Deductions
            |--------------------------------------------------------------------------
            */
            $lateDeduction      = $hourlyRate * $lateHours;
            $undertimeDeduction = $hourlyRate * $undertimeHours;
            $absentDeduction    = $hourlyRate * $absentHours;

            /*
            |--------------------------------------------------------------------------
            | Earnings
            |--------------------------------------------------------------------------
            */
            $nsdPay   = $hourlyRate * $nsdHours;
            $otPay    = $hourlyRate * $otHours * 1.25;
            $nsdOtPay = $hourlyRate * $nsdOtHours * 0.15;

            /*
            |--------------------------------------------------------------------------
            | Benefits
            |--------------------------------------------------------------------------
            */
            if (isset($benefits[$row->employee_id])) {
                foreach ($benefits[$row->employee_id] as $benefit) {

                    $amount = $benefit->amount / 12;

                    match ($benefit->name) {
                        'UNIFORM_ALLOWANCE' => $uniformAllowance = $amount,
                        'FUEL'              => $gasAllowance = $amount,
                        'RICE'              => $riceAllowance = $amount,
                        'LAUNDRY_ALLOWANCE' => $laundryAllowance = $amount,
                        default             => null,
                    };
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Other Income
            |--------------------------------------------------------------------------
            */
            if (isset($otherIncome[$row->empnum])) {
                foreach ($otherIncome[$row->empnum] as $income) {

                    match ($income->income_type) {
                        'Bus Marshal'           => $busMarshallAllowance = $income->amount,
                        'Monthly Home Subsidy'  => $monthlyHomeSubsidy = $income->amount,
                        'Cash Gift'             => $cashGift = $income->amount,
                        'LTI Cash Awards'       => $ltiCashAwards = $income->amount,
                        'Retention Bonus'       => $retentionBonus = $income->amount,
                        default                 => null,
                    };
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Deductions
            |--------------------------------------------------------------------------
            */
            if (isset($deductions[$row->empnum])) {
                foreach ($deductions[$row->empnum] as $deduction) {

                    match ($deduction->deduction_type) {
                        'SSS Salary Loan' => $sssLoan = $deduction->amount,
                        'Coop Loan'       => $coopLoan = $deduction->amount,
                        'Employee Savings'=> $employeeSavings = $deduction->amount,
                        'Pag-Ibig Loan'   => $hdmfLoan = $deduction->amount,
                        'Tax Adjustment'  => $taxAdjustment = $deduction->amount,
                        default           => null,
                    };
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */
            $mandatory = $row->sss_ee + $row->hdmf_ee + $row->phil_ee + $row->sss_ec;

            $gross =
                $basicPay +
                $nsdPay +
                $otPay +
                $nsdOtPay +
                $busMarshallAllowance +
                $monthlyHomeSubsidy +
                $cashGift +
                $ltiCashAwards +
                $retentionBonus -

                $lateDeduction -
                $undertimeDeduction -
                $absentDeduction;

            $deminimis =
                $uniformAllowance +
                $gasAllowance +
                $riceAllowance +
                $laundryAllowance +
                ($row->meal ?? 0) +
                ($row->adjusted_meal ?? 0);

            $taxableIncome = $gross - $mandatory;

            $withholdingTax = $this->computeWithholdingTax(
                $taxableIncome,
                $taxMode
            );

            $totalDeduction =
                $mandatory +
                $lateDeduction +
                $undertimeDeduction +
                $absentDeduction +
                $sssLoan +
                $coopLoan +
                $hdmfLoan +
                $employeeSavings +
                $taxAdjustment +
                $withholdingTax;

            $net = $gross + $deminimis - $totalDeduction;

            /*
            |--------------------------------------------------------------------------
            | Duplicate-Proof Save
            |--------------------------------------------------------------------------
            */
            PayrollRegister::updateOrCreate(
                [
                    'cutoff_id' => $cutoffId,
                    'empnum'    => $row->empnum,
                ],
                [
                    'empname'        => $row->empname,
                    'accountNumber'  => $row->account_number,
                    'payrollType'    => $row->pay_type,
                    'factor'         => $factor,

                    'annual_salary'  => $annualSalary,
                    'monthly_rate'   => $monthlyRate,
                    'hourly_rate'    => $hourlyRate,

                    'basic_pay'      => $basicPay,

                    'late'           => $lateDeduction,
                    'undertime'      => $undertimeDeduction,
                    'absent'         => $absentDeduction,

                    'nsdReg'         => $nsdPay,
                    'ot'             => $otPay,
                    'nsdOt'          => $nsdOtPay,

                    'uniformClothingAllowance' => $uniformAllowance,
                    'gasAllowance'   => $gasAllowance,
                    'riceAllowance'  => $riceAllowance,
                    'laundryAllowance'=> $laundryAllowance,

                    'busMarshallAllowance' => $busMarshallAllowance,
                    'monthlyHomeSubsidy'   => $monthlyHomeSubsidy,
                    'cashGift'             => $cashGift,
                    'ltiCashAwards'        => $ltiCashAwards,
                    'retentionBonus'       => $retentionBonus,

                    'sssEmployee' => $row->sss_ee,
                    'sssEmployer' => $row->sss_er,
                    'sssEc'       => $row->sss_ec,

                    'pagibigEe'   => $row->hdmf_ee,
                    'pagibigEr'   => $row->hdmf_er,

                    'philhealthEe'=> $row->phil_ee,
                    'philhealthEr'=> $row->phil_er,

                    'sssSalaryLoanAdj' => $sssLoan,
                    'coopLoan'         => $coopLoan,
                    'HdmfLoanAdj'      => $hdmfLoan,
                    'employeeSavings'  => $employeeSavings,
                    'taxAdjustment'    => $taxAdjustment,

                    'gross'           => $gross,
                    'employeeTax'     => $withholdingTax,
                    'totalDeduction'  => $totalDeduction,
                    'net'             => $net,
                    'atm'             => $net,
                ]
            );

            $this->updatePayrollYtd(
                $cutoffId,
                $year,
                $row,
                (float) $gross,
                (float) $taxableIncome,
                (float) $withholdingTax,
                (float) $net
            );
        }

      
    });

    PayrollStepsService::complete($cutoffId, 'payroll');

    return back()->with('success', 'Payroll register processed successfully.');
}

public function generatePayslip(Request $request, int $cutoffId)
{
    DB::transaction(function () use ($cutoffId) {

        /*
        |--------------------------------------------------------------------------
        | 1. LOAD CUTOFF
        |--------------------------------------------------------------------------
        */
        $cutoff = PayrollCutOff::findOrFail($cutoffId);
        $year = Carbon::parse($cutoff->payrollDate)->year;

        /*
        |--------------------------------------------------------------------------
        | 2. LOAD PAYROLL REGISTER
        |--------------------------------------------------------------------------
        */
        $rows = PayrollRegister::where('cutoff_id', $cutoffId)
            ->orderBy('empnum')
            ->get();

        if ($rows->isEmpty()) {
            throw new \Exception('No payroll register found for selected cutoff.');
        }

        /*
        |--------------------------------------------------------------------------
        | 3. PRELOAD PREVIOUS PAYSLIPS (YTD BASE)
        |--------------------------------------------------------------------------
        */
        $empnums = $rows->pluck('empnum')->unique();

        $previousPayslips = PayrollPayslip::whereIn('empnum', $empnums)
            ->where('cutoff_id', '<', $cutoffId)
            ->orderByDesc('cutoff_id')
            ->get()
            ->groupBy('empnum')
            ->map(fn ($items) => $items->first());

        /*
        |--------------------------------------------------------------------------
        | 4. SAFE NUMBER HELPER
        |--------------------------------------------------------------------------
        */
        $num = fn ($v) => round((float) ($v ?? 0), 2);

        /*
        |--------------------------------------------------------------------------
        | 5. PROCESS EACH EMPLOYEE
        |--------------------------------------------------------------------------
        */
        foreach ($rows as $row) {

            $prev = $previousPayslips[$row->empnum] ?? null;

            /*
            |--------------------------------------------------------------------------
            | EARNINGS (SAFE CASTING)
            |--------------------------------------------------------------------------
            */
            $earnings = [
                'basic_pay'         => $num($row->basic_pay),
                'nsdReg'            => $num($row->nsdReg),
                'ot'                => $num($row->ot),
                'nsdOt'             => $num($row->nsdOt),
                'rdReg'             => $num($row->rdReg),
                'rdOt'              => $num($row->rdOt),
                'nsdRdReg'          => $num($row->nsdRdReg),
                'nsdRdOt'           => $num($row->nsdRdOt),
                'lhReg'             => $num($row->lhReg),
                'lhOt'              => $num($row->lhOt),
                'nsdLh'             => $num($row->nsdLh),
                'nsdLhOt'           => $num($row->nsdLhOt),
                'shReg'             => $num($row->shReg),
                'shOt'              => $num($row->shOt),
                'nsdSh'             => $num($row->nsdSh),
                'nsdShOt'           => $num($row->nsdShOt),
                'lhrdReg'           => $num($row->lhrdReg),
                'lhrdOt'            => $num($row->lhrdOt),
                'nsdLhRd'           => $num($row->nsdLhRd),
                'nsdLhRdOt'         => $num($row->nsdLhRdOt),
                'shrdReg'           => $num($row->shrdReg),
                'shrdOt'            => $num($row->shrdOt),
                'nsdShRd'           => $num($row->nsdShRd),
                'nsdShRdOt'         => $num($row->nsdShRdOt),
                'dtrAdjustment'     => $num($row->dtrAdjustment),
                'otAdjustment'      => $num($row->otAdjustment),
                'medicalAssistance' => $num($row->medicalAssistance),
                'retentionBonus'    => $num($row->retentionBonus),
                'cashGift'          => $num($row->cashGift),
                'ltiCashAwards'     => $num($row->ltiCashAwards),
            ];

            /*
            |--------------------------------------------------------------------------
            | BENEFITS
            |--------------------------------------------------------------------------
            */
            $benefits = [
                'uniformClothingAllowance' => $num($row->uniformClothingAllowance),
                'transpoAllowance'         => $num($row->transpoAllowance),
                'laundryAllowance'         => $num($row->laundryAllowance),
                'medicalCashAllowance'     => $num($row->medicalCashAllowance),
                'mealOt'                   => $num($row->mealOt),
                'mealAllowanceAdj'         => $num($row->mealAllowanceAdj),
                'riceAllowance'            => $num($row->riceAllowance),
            ];

            /*
            |--------------------------------------------------------------------------
            | DEDUCTIONS (SAFE + NO ARRAY SUM BUGS)
            |--------------------------------------------------------------------------
            */
            $deductions = [
                'employeeTax'      => $num($row->employeeTax),
                'sssEmployee'      => $num($row->sssEmployee),
                'sssMpfEe'         => $num($row->sssMpfEe),
                'philhealthEe'      => $num($row->philhealthEe),
                'pagibigEe'        => $num($row->pagibigEe),
                'employeeSavings'   => $num($row->employeeSavings),
                'HdmfLoanAdj'       => $num($row->HdmfLoanAdj),
                'coopLoan'          => $num($row->coopLoan),
                'sssSalaryLoanAdj'  => $num($row->sssSalaryLoanAdj),
                'taxAdjustment'     => $num($row->taxAdjustment),
                'late'              => $num($row->late),
                'absent'            => $num($row->absent),
                'undertime'         => $num($row->undertime),
            ];

            /*
            |--------------------------------------------------------------------------
            | TOTALS (SAFE SUMMATION)
            |--------------------------------------------------------------------------
            */
            $grossPay = array_sum($earnings);
            $totalBenefits = array_sum($benefits);
            $totalIncome = $grossPay + $totalBenefits;
            $totalDeduction = array_sum($deductions);
            $netPay = $totalIncome - $totalDeduction;

            /*
            |--------------------------------------------------------------------------
            | YTD (NULL SAFE - FIXED CRASH POINT)
            |--------------------------------------------------------------------------
            */
            $ytdGross = ($prev->ytd_gross ?? 0) + $grossPay;
            $ytdTaxable = ($prev->ytd_taxable ?? 0) + $totalIncome;
            $ytdTax = ($prev->ytd_tax ?? 0) + $deductions['employeeTax'];
            $ytdSss = ($prev->ytd_sss ?? 0) + ($deductions['sssEmployee'] + $deductions['sssMpfEe']);
            $ytdPhilhealth = ($prev->ytd_philhealth ?? 0) + $deductions['philhealthEe'];
            $ytdPagibig = ($prev->ytd_pagibig ?? 0) + $deductions['pagibigEe'];
            $ytdNet = ($prev->ytd_net ?? 0) + $netPay;

            /*
            |--------------------------------------------------------------------------
            | SAVE PAYSLIP
            |--------------------------------------------------------------------------
            */
            PayrollPayslip::updateOrCreate(
                [
                    'cutoff_id' => $cutoffId,
                    'empnum'    => $row->empnum,
                ],
                array_merge(
                    [
                        'empname'         => $row->empname,

                        'gross_pay'       => $grossPay,
                        'total_benefits'  => $totalBenefits,
                        'total_income'    => $totalIncome,
                        'total_deduction' => $totalDeduction,
                        'net_pay'         => $netPay,

                        'ytd_gross'       => $ytdGross,
                        'ytd_taxable'     => $ytdTaxable,
                        'ytd_tax'         => $ytdTax,
                        'ytd_sss'         => $ytdSss,
                        'ytd_philhealth'  => $ytdPhilhealth,
                        'ytd_pagibig'     => $ytdPagibig,
                        'ytd_net'         => $ytdNet,

                        'updated_at'      => now(),
                    ],
                    $earnings,
                    $benefits,
                    $deductions
                )
            );
        }
    });

    PayrollStepsService::complete($cutoffId, 'payslip');

    return back()->with('success', 'Payslip generated successfully.');
}

/*
|--------------------------------------------------------------------------
| ADD THIS METHOD INSIDE PayrollController
|--------------------------------------------------------------------------
*/
private function updatePayrollYtd(
    int $cutoffId,
    int $year,
    object $row,
    float $gross,
    float $taxableIncome,
    float $withholdingTax,
    float $net
): void
{
    /*
    |--------------------------------------------------------------------------
    | GET PREVIOUS CUTOFF YTD OF SAME EMPLOYEE / SAME YEAR
    |--------------------------------------------------------------------------
    */
    $previous = DB::table('payroll_ytds')
        ->where('empnum', $row->empnum)
        ->where('year', $year)
        ->where('cutoff_id', '<', $cutoffId)
        ->orderByDesc('cutoff_id')
        ->first();

    $prevGross      = $previous->gross_income ?? 0;
    $prevTaxable    = $previous->taxable_income ?? 0;
    $prevTax        = $previous->withholding_tax ?? 0;
    $prevSss        = $previous->sss_employee ?? 0;
    $prevPhilhealth = $previous->philhealth_employee ?? 0;
    $prevPagibig    = $previous->pagibig_employee ?? 0;
    $prevNet        = $previous->net_pay ?? 0;

    /*
    |--------------------------------------------------------------------------
    | SAVE CURRENT CUTOFF SNAPSHOT YTD
    |--------------------------------------------------------------------------
    */
    DB::table('payroll_ytds')->updateOrInsert(
        [
            'empnum'    => $row->empnum,
            'cutoff_id' => $cutoffId,
            'year'      => $year,
        ],
        [
            'gross_income'        => round($prevGross + $gross, 2),
            'taxable_income'      => round($prevTaxable + $taxableIncome, 2),
            'withholding_tax'     => round($prevTax + $withholdingTax, 2),
            'sss_employee'        => round($prevSss + $row->sss_ee, 2),
            'philhealth_employee' => round($prevPhilhealth + $row->phil_ee, 2),
            'pagibig_employee'    => round($prevPagibig + $row->hdmf_ee, 2),
            'net_pay'             => round($prevNet + $net, 2),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]
    );
}

    private function computeWithholdingTax(int $taxable, string $payrollType)
    {
        if($payrollType == 'Daily'){
            if($taxable <= 685){
                return 0;
            }

            if($taxable > 685  && $taxable <= 1095){
                return ($taxable - 685) * 0.15;
            }

            if($taxable >= 1096  && $taxable <= 2191){
                return (($taxable - 1096) * 0.20) + 61.65;
            }

            if($taxable >= 2192  && $taxable <= 5478){
                return (($taxable - 2192) * 0.25) + 280.85;
            }

            if($taxable >= 5479  && $taxable <= 21917){
                return (($taxable - 5479) * 0.30) + 1102.60;
            }

            if($taxable >= 21918){
                return (($taxable - 21918) * 0.35) + 6034.30;
            }
        }elseif($payrollType == 'Weekly'){
            if($taxable <= 4808){
                return 0;
            }

            if($taxable > 4808  && $taxable <= 7691){
                return ($taxable - 4808) * 0.15;
            }

            if($taxable >= 7692  && $taxable <= 15384){
                return (($taxable - 7692) * 0.20) + 432.60;
            }

            if($taxable >= 15385  && $taxable <= 38461){
                return (($taxable - 15385) * 0.25) + 1971.20;
            }

            if($taxable >= 38462  && $taxable <= 153845){
                return (($taxable - 38462) * 0.30) + 7740.45;
            }

            if($taxable >= 153846){
                return (($taxable - 153846) * 0.35) + 42355.65;
            }
        }elseif($payrollType == 'Semi-Monthly'){
            if($taxable <= 10417){
                return 0;
            }

            if($taxable > 10417  && $taxable <= 16666){
                return ($taxable - 10417) * 0.15;
            }

            if($taxable >= 16667  && $taxable <= 33332){
                return (($taxable - 16667) * 0.20) + 937.50;
            }

            if($taxable >= 33333  && $taxable <= 83332){
                return (($taxable - 33333) * 0.25) + 4270.70;
            }

            if($taxable >= 83333  && $taxable <= 333332){
                return (($taxable - 83333) * 0.30) + 16770.70;
            }

            if($taxable >= 333333){
                return (($taxable - 333333) * 0.35) + 91770.70;
            }
        }else{
            if($taxable <= 20833){
                return 0;
            }

            if($taxable > 20833  && $taxable <= 33332){
                return ($taxable - 20883) * 0.15;
            }

            if($taxable >= 33333  && $taxable <= 66666){
                return (($taxable - 33333) * 0.20) + 1875.20;
            }

            if($taxable >= 66666  && $taxable <= 166666){
                return (($taxable - 66667) * 0.25) + 8541.80;
            }

            if($taxable >= 166667  && $taxable <= 666666){
                return (($taxable - 166667) * 0.30) + 33541.80;
            }

            if($taxable >= 666667){
                return (($taxable - 666667) * 0.35) + 183541.80;
            }
        }
    }

    /* =========================================================
        SKIP
    ========================================================= */
    public function skip($cutoff)
    {
        PayrollStepsService::skip($cutoff, 'other_income');

        return back()->with('success', 'Other income skipped');
    }

    public function skipDeduction($cutoff)
    {
        PayrollStepsService::skip($cutoff, 'deduction');

        return back()->with('success', 'Deduction skipped');
    }

    public function skipSSS($cutoff)
    {
        PayrollStepsService::skip($cutoff, 'sss');

        return back()->with('success', 'SSS skipped');
    }

    public function processBank($cutoffId)
    {
        DB::transaction(function () use ($cutoffId) {

            BankFile::where('cutoff_id', $cutoffId)->delete();

            $rows = PayrollRegister::where('cutoff_id', $cutoffId)->get();

            foreach ($rows as $row) {

                BankFile::create([
                    'cutoff_id'        => $cutoffId,
                    'empnum'           => $row->empnum,
                    'employee_name'    => $row->empname,
                    'account_number'   => $row->accountNumber,
                    'amount'           => $row->atm,
                    'reference_number' => 'PAY-' . $cutoffId . '-' . $row->empnum,
                ]);
            }
        });

        PayrollStepsService::complete($cutoffId, 'bank');

        return back()->with('success', 'Bank file generated.');
    }

    public function downloadBankFile($cutoffId)
    {
        $rows = BankFile::where('cutoff_id', $cutoffId)->get();

        $filename = "BPI_Bank_File_$cutoffId.csv";

        return response()->streamDownload(function () use ($rows) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Account Number',
                'Amount',
                'Employee Name',
                'Reference Number'
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->account_number,
                    number_format($row->amount, 2, '.', ''),
                    $row->employee_name,
                    $row->reference_number
                ]);
            }

            fclose($handle);

        }, $filename);
    }


}
