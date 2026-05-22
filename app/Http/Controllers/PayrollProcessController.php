<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\PayrollStepService;
use App\Models\{Timekeeping,
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
EmployeeCompensation
};

class PayrollProcessController extends Controller
{
    public function index($cutoffId = null)
    {
        // All cutoffs (limit to 12)
        $cutoffs = PayrollCutOff::orderByDesc('id')->take(12)->get();

        // Selected cutoff
        $cutoff = $cutoffId
            ? PayrollCutOff::find($cutoffId)
            : $cutoffs->first();

        if (!$cutoff) {

            $stepsStatus = [
                'timekeeping' => false,
                'other_income' => false,
                'deduction' => false,
                'medical' => false,
                'sss' => false,
                'pagibig' => false,
                'philhealth' => false,
                'payroll' => false,
                'bank' => false,
                'payslip' => false,
            ];

            return Inertia::render('admin/payrollprocess/Index', [
                'cutoff' => null,
                'cutoffs' => PayrollCutOff::orderByDesc('id')->take(12)->get(),
                'employees' => [],
                'stepsStatus' => $stepsStatus, // ✅ correct
                'otherIncomeUploadedAt' => null,
            ]);
        }

        
        $latestUpload = OtherIncome::where('cutoff_id', $cutoff->id)
            ->latest('uploaded_at')
            ->value('uploaded_at');


        $employees = TimekeepingProcess::where('payroll_cut_offs_id', $cutoff->id)
            ->get()
            ->map(function ($p) {
                return [
                    'empnum' => $p->empnum,
                    'totals' => [
                        'regularHours' => $p->regular_hours,
                        'nsd' => $p->nsd,
                        'overtime' => $p->overtime,
                        'overtimeNsd' => $p->overtime_nsd,
                        'overtimeLH' => $p->overtime_lh,
                        'overtimeSH' => $p->overtime_sh,
                        'overtimeLHRD' => $p->overtime_lhrd,
                        'overtimeSHRD' => $p->overtime_shrd,
                        'overtimeRD' => $p->overtime_rd,
                        'overtimeNsdLH' => $p->overtime_nsd_lh,
                        'overtimeNsdSH' => $p->overtime_nsd_sh,
                        'overtimeNsdLHRD' => $p->overtime_nsd_lhrd,
                        'overtimeNsdSHRD' => $p->overtime_nsd_shrd,
                        'overtimeNsdRD' => $p->overtime_nsd_rd,
                        'late' => $p->late,
                        'undertime' => $p->undertime,
                    ],
                ];
            });
        
        $otherIncomeRows = OtherIncome::where('cutoff_id', $cutoff->id)
            ->orderBy('empnum')
            ->get(['id','empnum','empname','income_type','amount','is_taxable']);

        $stepsStatus = [
            'timekeeping' => TimekeepingProcess::where('payroll_cut_offs_id', $cutoff->id)->exists(),
            'other_income' => OtherIncome::where('cutoff_id', $cutoff->id)->exists(),
            'deduction' => false,
            'medical' => false,
            'sss' => false,
            'pagibig' => false,
            'philhealth' => false,
            'payroll' => false,
            'bank' => false,
            'payslip' => false, // next step later
        ];

        //return Inertia::render('admin/payrollprocess/Index', [
        return Inertia::render('payroll/PayrollProcess', [
            'cutoff' => $cutoff,
            'cutoffs' => $cutoffs,
            'employees' => $employees,
            'stepsStatus' => $stepsStatus,
            'otherIncomeRows' => $otherIncomeRows,
            'otherIncomeUploadedAt' => $latestUpload,
        ]);
    }

    public function showTimekeeping(PayrollCutOff $cutoff)
    {
        $cutoffs = PayrollCutOff::orderByDesc('id')->take(12)->get();

        $timekeeping = TimekeepingProcess::where('payroll_cut_offs_id', $cutoff->id)
            ->orderBy('empnum')
            ->get([
                'empnum',
                'reg',
                'nsd_reg',
                'overtime_reg',
                'overtime_nsd_reg',
                'overtime_lh',
                'overtime_lh_8',
                'overtime_lh_12',
                'overtime_lhrd',
                'overtime_lhrd_8',
                'overtime_lhrd_12',
                'overtime_nsd_lh',
                'overtime_sh',
                'overtime_sh_8',
                'overtime_sh_12',
                'overtime_shrd',
                'overtime_shrd_8',
                'overtime_shrd_12',
                'overtime_nsd_sh',
                'overtime_nsd_lhrd',
                'overtime_nsd_shrd',
                'overtime_rd',
                'overtime_rd_8',
                'overtime_rd_12',
                'overtime_nsd_rd',
                'late_reg',
                'undertime',
                'absent',
                'adjusted_hours',
                'adjusted_nsd',
                'adjusted_ot_hours',
                'adjusted_ot_nsd'
            ]);

        // ✅ ADD THIS
        $otherIncomeRows = OtherIncome::where('cutoff_id', $cutoff->id)
            ->orderBy('empnum')
            ->get(['id','empnum','empname','income_type','amount','is_taxable']);

        $latestUpload = OtherIncome::where('cutoff_id', $cutoff->id)
            ->latest('uploaded_at')
            ->value('uploaded_at');
        
        $deductionRows = EmployeeDeduction::where('cutoff_id', $cutoff->id)
            ->orderBy('empnum')
            ->get(['id','empnum','empname','deduction_type','amount','is_pre_tax']);

        $latestDeductionUpload = EmployeeDeduction::where('cutoff_id', $cutoff->id)
            ->latest('uploaded_at')
            ->value('uploaded_at');

        $medicalRows = PayrollMedical::whereDate('cutoff_id', $cutoff->id)
            ->orderBy('empnum')
            ->get([
                'empnum',
                'empname',
                'total_amount',
                'processed_at'
            ]);

        $sssRows = SssContribution::whereDate('cutoff_id', $cutoff->id)
            ->orderBy('empnum')
            ->get([
                'empnum',
                'empname',
                'sss_number',
                'year',
                'month',
                'employee',
                'employer',
                'ec',
                'processed_at'
            ]);

        $pagibigRows = PagIbigContribution::where('cutoff_id', $cutoff->id)
            ->orderBy('empnum')
            ->get([
                'empnum',
                'empname',
                'pagibig_number',
                'year',
                'month',
                'employee',
                'employer',
                'processed_at'
            ]);

        $philhealthRows = PhilHealthContribution::where('cutoff_id', $cutoff->id)
            ->orderBy('empnum')
            ->get([
                'empnum',
                'empname',
                'philhealth_number',
                'year',
                'month',
                'employee',
                'employer',
                'processed_at'
            ]);

        $payrollRows = PayrollRegister::where('cutoff_id', $cutoff->id)->get();

        return Inertia::render('admin/payrollprocess/Index', [
            'cutoff' => $cutoff,
            'cutoffs' => $cutoffs,
            'selectedCutoffId' => $cutoff->id,
            'timekeepingRows' => $timekeeping,
            'otherIncomeRows' => $otherIncomeRows, 
            'otherIncomeUploadedAt' => $latestUpload, 
            'employeeDeductionRows' => $deductionRows,
            'medicalRows' => $medicalRows,
            'sssRows' => $sssRows,
            'pagibigRows' => $pagibigRows,
            'philhealthRows' => $philhealthRows,
            'employeeDeductionUploadedAt' => $latestDeductionUpload,
            'payrollRows' => $payrollRows,
            'employees' => [],
            'stepsStatus' => [
                'timekeeping' => $timekeeping->isNotEmpty(),
                'other_income' => $otherIncomeRows->isNotEmpty(), 
                'deduction' => $deductionRows->isNotEmpty(),
                'medical' => PayrollMedical::where('cutoff_id', $cutoff->id)
    ->whereNotNull('processed_at')
    ->exists(),
                'sss' => SssContribution::where('cutoff_id', $cutoff->id)
    ->whereNotNull('processed_at')
    ->exists(),
                'pagibig' => $pagibigRows->isNotEmpty(),
                'philhealth' => $philhealthRows->isNotEmpty(),
                'payroll' => false,
                'bank' => false,
                'payslip' => false,
            ],
        ]);
    }

    /**
     * Display the payroll process page for a cutoff.
     */
    public function processTimekeeping($cutoffId)
    {
        $step = 'timekeeping';

        try {
            PayrollStepService::start($cutoffId, $step);

            $cutoff = PayrollCutOff::findOrFail($cutoffId);

            DB::transaction(function() use ($cutoff) {

                TimekeepingProcess::where('payroll_cutoff_id', $cutoff->id)->delete();

                $employees = Timekeeping::whereBetween('dated', [
                    $cutoff->cutOffStart,
                    $cutoff->cutOffEnd
                ])
                ->select('empnum')
                ->distinct()
                ->pluck('empnum');

            foreach ($employees as $empnum) {

                $tkRecords = Timekeeping::where('empnum', $empnum)
                    ->whereBetween('dated', [$cutoff->cutOffStart, $cutoff->cutOffEnd])
                    ->get();

                $otRecords = Overtime::where('empnum', $empnum)
                    ->whereBetween('overtimeDate', [$cutoff->cutOffStart, $cutoff->cutOffEnd])
                    ->whereIn('status', ['Approved', 'Pending'])
                    ->get();

                $totals = [
                    'regular_hours'      => 0,
                    'nsd'               => 0,
                    'overtime'          => 0,
                    'overtime_nsd'      => 0,
                    'overtime_lh'       => 0,
                    'overtime_sh'       => 0,
                    'overtime_lhrd'     => 0,
                    'overtime_shrd'     => 0,
                    'overtime_rd'       => 0,
                    'overtime_nsd_lh'   => 0,
                    'overtime_nsd_sh'   => 0,
                    'overtime_nsd_lhrd' => 0,
                    'overtime_nsd_shrd' => 0,
                    'overtime_nsd_rd'   => 0,
                    'late'              => 0,
                    'undertime'         => 0,
                ];

                foreach ($tkRecords as $tk) {
                    switch ($tk->dayType) {
                        case 'REG':
                            $totals['regular_hours'] += $tk->regHours ?? 0;
                            $totals['nsd'] += $tk->nsd ?? 0;
                            $totals['overtime'] += $tk->overtime ?? 0;
                            break;
                        case 'LH':
                            $totals['overtime_lh'] += $tk->overtime ?? 0;
                            $totals['overtime_nsd_lh'] += $tk->nsd ?? 0;
                            break;
                        case 'SH':
                            $totals['overtime_sh'] += $tk->overtime ?? 0;
                            $totals['overtime_nsd_sh'] += $tk->nsd ?? 0;
                            break;
                        case 'LHRD':
                            $totals['overtime_lhrd'] += $tk->overtime ?? 0;
                            $totals['overtime_nsd_lhrd'] += $tk->nsd ?? 0;
                            break;
                        case 'SHRD':
                            $totals['overtime_shrd'] += $tk->overtime ?? 0;
                            $totals['overtime_nsd_shrd'] += $tk->nsd ?? 0;
                            break;
                        case 'RD':
                            $totals['overtime_rd'] += $tk->overtime ?? 0;
                            $totals['overtime_nsd_rd'] += $tk->nsd ?? 0;
                            break;
                    }

                    $totals['late'] += $tk->late ?? 0;
                    $totals['undertime'] += $tk->undertime ?? 0;
                }

                foreach ($otRecords as $ot) {
                    $tk = Timekeeping::find($ot->timekeeping_id);
                    if (!$tk) continue;

                    switch ($tk->dayType) {
                        case 'REG':
                            $totals['overtime'] += $ot->hours ?? 0;
                            $totals['nsd'] += $ot->nsd ?? 0;
                            break;
                        case 'LH':
                            $totals['overtime_lh'] += $ot->hours ?? 0;
                            $totals['overtime_nsd_lh'] += $ot->nsd ?? 0;
                            break;
                        case 'SH':
                            $totals['overtime_sh'] += $ot->hours ?? 0;
                            $totals['overtime_nsd_sh'] += $ot->nsd ?? 0;
                            break;
                        case 'LHRD':
                            $totals['overtime_lhrd'] += $ot->hours ?? 0;
                            $totals['overtime_nsd_lhrd'] += $ot->nsd ?? 0;
                            break;
                        case 'SHRD':
                            $totals['overtime_shrd'] += $ot->hours ?? 0;
                            $totals['overtime_nsd_shrd'] += $ot->nsd ?? 0;
                            break;
                        case 'RD':
                            $totals['overtime_rd'] += $ot->hours ?? 0;
                            $totals['overtime_nsd_rd'] += $ot->nsd ?? 0;
                            break;
                    }
                }

                TimekeepingProcess::updateOrCreate(
                    [
                        'payroll_cutoff_id' => $cutoff->id,
                        'empnum' => $empnum
                    ],
                    $totals
                );
            }
        });

            PayrollStepService::complete($cutoffId, $step);

            return back()->with('success', 'Timekeeping processed');

        } catch (\Exception $e) {

            PayrollStepService::fail($cutoffId, $step, $e->getMessage());

            return back()->with('error', $e->getMessage());
        }
    }


    public function uploadEmployeeDeduction(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'cutoff_id' => 'required|exists:payroll_cut_offs,id',
        ]);

        $file = $request->file('file');
        $cutoffId = $request->cutoff_id;

        DB::transaction(function () use ($file, $cutoffId) {

            // ✅ Delete existing deductions for this cutoff
            EmployeeDeduction::where('cutoff_id', $cutoffId)->delete();

            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());

            $sheet = $spreadsheet->getSheet(0);
            $rows = $sheet->toArray(null, true, true, true);

            foreach ($rows as $index => $row) {

                // ✅ Skip header row
                if ($index === 1) continue;

                EmployeeDeduction::create([
                    'cutoff_id'      => $cutoffId,
                    'empnum'         => $row['A'] ?? null,
                    'empname'        => $row['B'] ?? null,
                    'deduction_type' => $row['C'] ?? null,
                    'amount'         => isset($row['D'])
                        ? str_replace(',', '', $row['D'])
                        : 0,
                    'is_pre_tax'     => strtolower($row['E'] ?? '') === 'yes',
                    'uploaded_at'    => now(),
                ]);
            }
        });

        return back()->with('success', 'Employee deductions uploaded successfully.');
    }

    public function processMedical($cutoffId)
    {
        $step = 'medical';

        try {
            PayrollStepService::start($cutoffId, $step);

            DB::transaction(function () use ($cutoffId) {

                PayrollMedical::where('cutoff_id', $cutoffId)->delete();

                $records = Medical::where('status', 'Approved')->get()
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

            PayrollStepService::complete($cutoffId, $step);

            return back()->with('success', 'Medical done');

        } catch (\Exception $e) {

            PayrollStepService::fail($cutoffId, $step, $e->getMessage());

            return back()->with('error', $e->getMessage());
        }
    }


    public function processSSS(Request $request, $cutoffId)
    {
        $request->validate([
            'year' => 'required|digits:4',
            'month' => 'required|digits_between:1,2'
        ]);

        $cutoff = PayrollCutOff::findOrFail($cutoffId);

        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($year, $month, $cutoff) {

            // Delete previous SSS for this cutoff + period
            SssContribution::where('year', $year)
                ->where('month', $month)
                ->where('cutoff_id', $cutoff->id) // if you add cutoff_id column (recommended)
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
                    'cutoff_id'   => $cutoff->id, // add this column if not yet
                    'empnum'      => $user->empnum,
                    'empname'     => $user->name,
                    'sss_number'  => $sssNumber,
                    'year'        => $year,
                    'month'       => $month,
                    'employee'    => $sssBracket->employee_total,
                    'employer'    => $sssBracket->employer_regular + $sssBracket->employer_mpf,
                    'ec'          => $sssBracket->employer_ec,
                    'processed_at'=> now(),
                ]);
            }
        });

        return back()->with('success', 'SSS contributions generated successfully.');
    }

    public function processPagibig(Request $request, $cutoffId)
    {
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

        return back()->with('success', 'Pag-Ibig contributions generated successfully.');
    }

    public function processPhilHealth(Request $request, $cutoffId)
    {
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

        return back()->with('success', 'Pag-Ibig contributions generated successfully.');
    }

    public function processPayrollRegister($cutoffId)
    {
        DB::beginTransaction();

        try {

            // ✅ 1. Delete existing records (safe re-run)
            PayrollRegister::where('cutoff_id', $cutoffId)->delete();
            $cutoff = PayrollCutOff::findOrFail($cutoffId);
            $payday = Carbon::parse($cutoff->payrollDate)->day;

            // ✅ 2. Get all required data in ONE QUERY
            $rows = DB::table('timekeeping_processes as tk')
                ->join('employees as e', 'e.empnum', '=', 'tk.empnum')
                ->join('employee_compensations as c', 'c.employee_id', '=', 'e.id')
                ->join('employee_personal_infos as p', 'p.employee_id', '=', 'e.id')
                ->join('sss_contributions as sss', 'sss.empnum', '=', 'e.empnum')
                ->join('pag_ibig_contributions as hdmf', 'hdmf.empnum', '=', 'e.empnum')
                ->join('philhealth_contributions as phil', 'phil.empnum', '=', 'e.empnum')
                ->where('tk.payroll_cut_offs_id', $cutoffId)
                ->select(
                    'tk.empnum',
                    'e.id as employee_id',
                    'e.name',
                    'c.base_salary',
                    'c.pay_type',
                    'c.factor',
                    'p.account_number',
                    'e.standard_hours',
                    'tk.late_reg',
                    'tk.absent',
                    'tk.meal',
                    'tk.adjusted_meal',
                    'sss.employee as semployee',
                    'sss.employer as semployer',
                    'sss.ec',
                    'hdmf.employee as hemployee',
                    'hdmf.employer as hemployer',
                    'phil.employee as phemployee',
                    'phil.employer as phemployer'
                )
                ->get();
            
            $benefits = DB::table('employee_benefits')
                ->where('payday', (string)$payday)
                ->get()
                ->groupBy('employee_id');
            
            $otherIncomes = DB::table('other_incomes')
                ->where('cutoff_id', $cutoffId)
                ->get()
                ->groupBy('empnum');

            $deductions = DB::table('employee_deductions')
                ->where('cutoff_id', $cutoffId)
                ->get()
                ->groupBy('empnum');

            $lhPay = 0;
            $lhrdPay = 0;
            $shPay = 0;
            $shrdPay = 0;
            $rdPay = 0;
            $busMarshalAllownace = 0;
            $monthlyHomeSubsidy = 0;
            $cashGift = 0;
            $ltiCashAwards = 0;
            $retentionBonus = 0;
            $sssLoan = 0;
            $hdmfLoan = 0;
            $coopLoan = 0;
            $taxAdjustment = 0;

            // ✅ 3. Prepare bulk insert array
            $insertData = [];

            foreach ($rows as $row) {

                $annualSalary = $row->base_salary;
                $factor = $row->factor;
                $lateHours = $row->late_reg ?? 0;
                $undertimeHours = $row->undertime ?? 0;
                $absentHours = $row->absent ?? 0;
                $nsdHours = $row->nsd_reg ?? 0;
                $otHours = $row->overtime_reg ?? 0;
                $nsdOtHours = $row->overtime_nsd_reg ?? 0;
                $rdHours = $row->overtime_rd ?? 0;
                $nsdRdHours = $row->overtime_nsd_rd ?? 0;

                $lhHours = $row->overtime_lh ?? 0;
                $nsdLhHours = $row->overtime_nsd_lh ?? 0;
                $shHours = $row->overtime_sh ?? 0;
                $nsdShHours = $row->overtime_nsd_sh ?? 0;
                $lhrdHours = $row->overtime_lhrd ?? 0;
                $nsdLhrdHours = $row->overtime_nsd_lhrd ?? 0;
                $shrdHours = $row->overtime_shrd ?? 0;
                $nsdShrdHours = $row->overtime_nsd_shrd ?? 0;

                $adjHours = $row->adjusted_hours ?? 0;
                $adjNsd = $row->adjusted_nsd ?? 0;
                $adjOtHours = $row->adjusted_ot_hours ?? 0;
                $adjOtNsd = $row->adjusted_ot_nsd ?? 0;


                // Computations
                $monthlyRate = $annualSalary / 12;
                $hourlyRate = $monthlyRate / $factor;
                $basicPay = $monthlyRate/2;
                $lateDeduction = $hourlyRate * $lateHours;
                $undertimeDeduction = $hourlyRate * $undertimeHours;
                $absentDeduction = $hourlyRate * $absentHours;
                $nsdPay = $hourlyRate * $nsdHours;
                $otPay = $hourlyRate * $otHours * 0.0125;
                $nsdOtPay = $hourlyRate * $nsdOtHours * 0.15;
                $nsdRdPay = $hourlyRate * $nsdRdHours * 1.5;

                if($row->standard_hours == '40'){
                    if($lhHours > 9){
                        $lhPay = $hourlyRate * 8 * 2;
                        $lhOtPay = $hourlyRate * ($lhHours - 8) * 2.6;
                        $shPay = $hourlyRate * 8 * 0.013;
                        $shOtPay = $hourlyRate * ($shHours - 8) * 0.013;
                        $rdPay = $hourlyRate * 8 * 0.013;
                        $rdOtPay = $hourlyRate * ($rdHours - 8) * 0.0169;
                        $lhrdPay = $hourlyRate * 8;
                        $lhrdOtPay = $hourlyRate * ($lhrdHours - 8);
                        $shrdPay = $hourlyRate * 8;
                        $shrdOtPay = $hourlyRate * ($shrdHours - 8);
                    }else{
                        $lhPay = $hourlyRate * $lhHours * 2.6;
                        $lhOtPay = '0.00';
                        $shPay = $hourlyRate * $shHours * 0.013;
                        $shOtPay = '0.00';
                        $rdPay = $hourlyRate * $rdHours * 0.013;
                        $rdOtPay = '0.00';
                        $lhrdPay = $hourlyRate * $lhrdHours;
                        $lhrdOtPay = '0.00';
                        $shrdPay = $hourlyRate * $shrdHours;
                        $shrdOtPay = '0.00';
                    }

                }else{
                    if($lhHours > 12){
                        $lhPay = $hourlyRate * 12 * 2;
                        $lhOtPay = $hourlyRate * ($lhHours - 12) * 2.6;
                        $shPay = $hourlyRate * 12 * 0.013;
                        $shOtPay = $hourlyRate * ($shHours - 12) * 0.013;
                        $rdPay = $hourlyRate * 12 * 0.013;
                        $rdOtPay = $hourlyRate * ($rdHours - 12) * 0.0169;
                        $lhrdPay = $hourlyRate * 12;
                        $lhrdOtPay = $hourlyRate * ($lhrdHours - 12);
                        $shrdPay = $hourlyRate * 12;
                        $shrdOtPay = $hourlyRate * ($shrdHours - 12);
                    }else{
                        $lhPay = $hourlyRate * $lhHours * 2.6;
                        $lhOtPay = '0.00';
                        $shPay = $hourlyRate * $shHours * 0.013;
                        $shOtPay = '0.00';
                        $rdPay = $hourlyRate * $rdHours * 0.013;
                        $rdOtPay = '0.00';
                        $lhrdPay = $hourlyRate * $lhrdHours;
                        $lhrdOtPay = '0.00';
                        $shrdPay = $hourlyRate * $shrdHours;
                        $shrdOtPay = '0.00';
                    }
                }
                
                $nsdLhPay = $hourlyRate * $nsdLhHours * 0.2;
                $nsdShPay = $hourlyRate * $nsdShHours * 0.43;
                $nsdLhrdPay = $hourlyRate * $nsdLhrdHours;
                $nsdShrdPay = $hourlyRate * $nsdShrdHours;
                $adjPay = $hourlyRate * $adjHours;
                $adjNsdPay = $hourlyRate * $adjNsd;
                $adjOtPay = $hourlyRate * $adjOtHours;
                $adjOtNsdPay = $hourlyRate * $adjOtNsd;

                $uniformAllowance = 0;
                $gasAllowance = 0;
                $riceAllowance = 0;
                $laundryAllowance = 0;

                if (isset($benefits[$row->employee_id])) {

                    foreach ($benefits[$row->employee_id] as $benefit) {

                        $amount = $benefit->amount / 12;

                        switch ($benefit->name) {

                            case 'UNIFORM_ALLOWANCE':
                                $uniformAllowance = $amount;
                                break;

                            case 'FUEL':
                                $gasAllowance = $amount;
                                break;

                            case 'RICE':
                                $riceAllowance = $amount;
                                break;

                            case 'LAUNDRY_ALLOWANCE':
                                $laundryAllowance = $amount;
                                break;
                        }
                    }
                }

                if (isset($otherIncomes[$row->empnum])) {

                    foreach ($otherIncomes[$row->empnum] as $otherIncome) {

                        $amount = $otherIncome->amount;

                        switch ($otherIncome->income_type) {

                            case 'Bus Marshal':
                                $busMarshalAllownace = $amount;
                                break;

                            case 'Monthly Home Subsidy':
                                $monthlyHomeSubsidy = $amount;
                                break;

                            case 'Cash Gift':
                                $cashGift = $amount;
                                break;

                            case 'LTI Cash Awards':
                                $ltiCashAwards = $amount;
                                break;
                            
                            case 'Retention Bonus':
                                $retentionBonus = $amount;
                                break;
                        }
                    }
                }

                if (isset($deductions[$row->empnum])) {

                    foreach ($deductions[$row->empnum] as $deduction) {

                        $amount = $deduction->amount;

                        switch ($deduction->deduction_type) {

                            case 'SSS Salary Loan':
                                $sssLoan = $amount;
                                break;

                            case 'Coop Loan':
                                $coopLoan = $amount;
                                break;

                            case 'Employee Savings':
                                $employeeSavings = $amount;
                                break;

                            case 'Pag-Ibig Loan':
                                $hdmfLoan = $amount;
                                break;
                            
                            case 'Tax Adjustment':
                                $taxAdjustment = $amount;
                                break;
                        }
                    }
                }

                $allDeduction = $row->semployee + $row->hemployee + $row->phemployee + $row->ec;
                $allPay = ($basicPay + $nsdPay + $otPay + $nsdOtPay + $rdPay + $nsdOtPay + $rdPay + $nsdRdPay + $rdOtPay + $lhPay + $lhOtPay + $nsdLhPay + $shPay + $shOtPay + $nsdShPay + $lhrdPay + $lhrdOtPay + $nsdLhrdPay + $shrdPay + $shrdOtPay + $nsdShrdPay + $adjPay + $adjOtPay + $retentionBonus + $ltiCashAwards + $cashGift + $monthlyHomeSubsidy + $busMarshalAllownace) - ($lateDeduction + $absentDeduction + $undertimeDeduction);
                $deminimisPay = $row->meal + $row->adjusted_meal + $uniformAllowance + $gasAllowance + $riceAllowance + $laundryAllowance;
                $totalDeduction = $row->semployee + $row->hemployee + $row->phemployee + $row->ec + $lateDeduction + $absentDeduction + $undertimeDeduction +$sssLoan + $coopLoan + $hdmfLoan + $taxAdjustment;

                $taxableIncome = $allPay - $allDeduction;

                $withholdingTax = $this->computeWithholdingTax($taxableIncome,$row->pay_type);
                $netPay = $taxableIncome + $deminimisPay - $withholdingTax;

                $insertData[] = [
                    'cutoff_id'      => $cutoffId,
                    'empnum'         => $row->empnum,
                    'empname'        => $row->name,
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
                    'rdReg'          => $rdPay,
                    'nsdRdReg'       => $nsdRdPay,
                    'rdOt'           => $rdOtPay,
                    'lhReg'          => $lhPay,
                    'lhOt'           => $lhOtPay,
                    'nsdLh'          => $nsdLhPay,
                    'shReg'          => $shPay,
                    'shOt'           => $shOtPay,
                    'nsdSh'          => $nsdShPay,
                    'lhrdReg'        => $lhrdPay,
                    'lhrdOt'         => $lhrdOtPay,
                    'nsdLhRd'        => $nsdLhrdPay,
                    'shrdReg'        => $shrdPay,
                    'shrdOt'         => $shrdOtPay,
                    'nsdShRd'        => $nsdShrdPay,
                    'dtrAdjustment'  => $adjPay,
                    'otAdjustment'   => $adjOtPay,
                    'mealOt'         => $row->meal,
                    'mealAllowanceAdj'=>$row->adjusted_meal,
                    'uniformClothingAllowance' => $uniformAllowance,
                    'gasAllowance'   => $gasAllowance,
                    'riceAllowance'  => $riceAllowance,
                    'laundryAllowance' => $laundryAllowance,
                    'busMarshallAllowance'=> $busMarshalAllownace,
                    'monthlyHomeSubsidy'=> $monthlyHomeSubsidy,
                    'cashGift'       => $cashGift,
                    'ltiCashAwards'  => $ltiCashAwards,
                    'retentionBonus' => $retentionBonus,
                    'sssSalaryLoanAdj'=> $sssLoan,
                    'HdmfLoanAdj'    => $hdmfLoan,
                    'coopLoan'       => $coopLoan,
                    'taxAdjustment'  => $taxAdjustment,
                    'sssEmployee'    => $row->semployee,
                    'sssEmployer'    => $row->semployer,
                    'sssEc'          => $row->ec,
                    'pagibigEe'      => $row->hemployee,
                    'pagibigEr'      => $row->hemployer,
                    'philhealthEe'   => $row->phemployee,
                    'philhealthEr'   => $row->phemployer,
                    'totalDeduction' => $totalDeduction,
                    'gross'          => $taxableIncome,
                    'employeeTax'    => $withholdingTax,
                    'net'            => $netPay,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }

            // ✅ 4. Bulk insert (VERY FAST)
            if (!empty($insertData)) {
                DB::table('payroll_registers')->insert($insertData);
            }

            // ✅ 5. Mark step as completed
 //           DB::table('payroll_cutoffs')
 //               ->where('id', $cutoffId)
 //               ->update([
 //                   'payroll_processed' => true
  //              ]);

            DB::commit();

            return back()->with('success', 'Payroll Register Generated Successfully');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    private function computeWithholdingTax($taxable, $payrollType)
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

    public function getStatus($cutoffId)
    {
        $steps = [
            'timekeeping',
            'other_income',
            'deduction',
            'medical',
            'sss',
            'pagibig',
            'philhealth',
            'payroll',
            'bank',
            'payslip'
        ];

        $statuses = DB::table('payroll_process_statuses')
            ->where('cutoff_id', $cutoffId)
            ->pluck('status', 'step');

        $result = [];
        $completed = 0;

        foreach ($steps as $step) {
            $status = $statuses[$step] ?? 'pending';

            if ($status === 'completed' || $status === 'skipped') {
                $completed++;
            }

            $result[] = [
                'key' => $step,
                'status' => $status
            ];
        }

        $progress = round(($completed / count($steps)) * 100);

        return response()->json([
            'steps' => $result,
            'progress' => $progress
        ]);
    }

    public function retry($cutoffId, $step)
    {
        PayrollStepService::reset($cutoffId, $step);

        return match($step) {
            'timekeeping' => $this->processTimekeeping($cutoffId),
            'medical' => $this->processMedical($cutoffId),
            'sss' => $this->processSSS(request(), $cutoffId),
            'pagibig' => $this->processPagibig(request(), $cutoffId),
            'philhealth' => $this->processPhilHealth(request(), $cutoffId),
            'payroll' => $this->processPayrollRegister($cutoffId),
            default => back()->with('error', 'Invalid step')
        };
    }

    public function rollback($cutoffId, $step)
    {
        switch ($step) {

            case 'timekeeping':
                TimekeepingProcess::where('payroll_cutoff_id', $cutoffId)->delete();
                break;

            case 'medical':
                PayrollMedical::where('cutoff_id', $cutoffId)->delete();
                break;

            case 'sss':
                SssContribution::where('cutoff_id', $cutoffId)->delete();
                break;

            case 'pagibig':
                PagIbigContribution::where('cutoff_id', $cutoffId)->delete();
                break;

            case 'philhealth':
                PhilHealthContribution::where('cutoff_id', $cutoffId)->delete();
                break;

            case 'payroll':
                PayrollRegister::where('cutoff_id', $cutoffId)->delete();
                break;
        }

        PayrollStepService::reset($cutoffId, $step);

        return back()->with('success', 'Rollback completed');
    }


}
