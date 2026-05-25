<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\PayrollPayslip;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPdf\Facades\Pdf;

class EmployeePayslipController extends Controller
{
    public function index()
    {
        $empnum = Auth::user()->empnum;

        $payslips = PayrollPayslip::query()
            ->join(
                'payroll_cut_offs',
                'payroll_cut_offs.id',
                '=',
                'payroll_payslips.cutoff_id'
            )
            ->select(
                'payroll_payslips.id',
                'payroll_payslips.cutoff_id',
                'payroll_payslips.empnum',
                'payroll_payslips.empname',
                'payroll_payslips.gross_pay',
                'payroll_payslips.net_pay',
                'payroll_cut_offs.payrollDate'
            )
            ->where('payroll_payslips.empnum', $empnum)
            ->latest('payroll_cut_offs.payrollDate')
            ->paginate(10);

        return inertia('employees/payslip/Index', [
            'payslips' => $payslips,
        ]);
    }

    public function download(int $id)
    {
        $empnum = Auth::user()->empnum;

        /*
        |--------------------------------------------------------------------------
        | PAYSLIP
        |--------------------------------------------------------------------------
        */
        $payslip = PayrollPayslip::query()

            ->join(
                'payroll_cut_offs',
                'payroll_cut_offs.id',
                '=',
                'payroll_payslips.cutoff_id'
            )

            ->where('payroll_payslips.id', $id)

            ->where(
                'payroll_payslips.empnum',
                $empnum
            )

            ->select(
                'payroll_payslips.*',
                'payroll_cut_offs.payrollDate'
            )

            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE DETAILS
        |--------------------------------------------------------------------------
        */
        $employee = Employee::query()

            ->leftJoin(
                'employee_personal_infos',
                'employee_personal_infos.employee_id',
                '=',
                'employees.id'
            )

            ->leftJoin(
                'employee_national_ids',
                'employee_national_ids.employee_id',
                '=',
                'employees.id'
            )

            ->select(
                'employees.*',

                'employee_national_ids.tin',

                'employee_national_ids.sss',
                'employee_national_ids.philhealth',
                'employee_national_ids.pagibig'
            )

            ->where('employees.empnum', $empnum)

            ->first();

        /*
        |--------------------------------------------------------------------------
        | YEAR
        |--------------------------------------------------------------------------
        */
        $year =
            \Carbon\Carbon::parse(
                $payslip->payrollDate
            )->year;

        /*
        |--------------------------------------------------------------------------
        | YEAR TO DATE
        |--------------------------------------------------------------------------
        */
        $ytd = PayrollPayslip::query()

            ->join(
                'payroll_cut_offs',
                'payroll_cut_offs.id',
                '=',
                'payroll_payslips.cutoff_id'
            )

            ->where(
                'payroll_payslips.empnum',
                $empnum
            )

            ->whereYear(
                'payroll_cut_offs.payrollDate',
                $year
            )

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            | Only include payrolls BEFORE OR EQUAL current payroll date
            |--------------------------------------------------------------------------
            */
            ->whereDate(
                'payroll_cut_offs.payrollDate',
                '<=',
                $payslip->payrollDate
            )

            ->selectRaw('

                /*
                |--------------------------------------------------------------------------
                | EARNINGS
                |--------------------------------------------------------------------------
                */
                COALESCE(SUM(basic_pay),0)
                    as basic_pay,

                COALESCE(SUM(gross_pay),0)
                    as gross_pay,

                COALESCE(SUM(total_income),0)
                    as total_income,

                /*
                |--------------------------------------------------------------------------
                | OTHER INCOME
                |--------------------------------------------------------------------------
                */
                COALESCE(
                    SUM(
                        ot +
                        nsdReg +
                        rdReg +
                        lhReg +
                        shReg +
                        lhrdReg +
                        shrdReg
                    ),
                0) as other_income,

                /*
                |--------------------------------------------------------------------------
                | BENEFITS
                |--------------------------------------------------------------------------
                */
                COALESCE(SUM(medicalCashAllowance),0)
                    as medicalCashAllowance,

                COALESCE(SUM(laundryAllowance),0)
                    as laundryAllowance,

                COALESCE(SUM(uniformClothingAllowance),0)
                    as uniformClothingAllowance,

                COALESCE(SUM(transpoAllowance),0)
                    as transpoAllowance,

                COALESCE(SUM(riceAllowance),0)
                    as riceAllowance,

                /*
                |--------------------------------------------------------------------------
                | DEDUCTIONS
                |--------------------------------------------------------------------------
                */
                COALESCE(SUM(sssMpfEe),0)
                    as sssMpfEe,

                COALESCE(SUM(sssEmployee),0)
                    as sssEmployee,

                COALESCE(SUM(philhealthEe),0)
                    as philhealthEe,

                COALESCE(SUM(pagibigEe),0)
                    as pagibigEe,

                COALESCE(SUM(employeeTax),0)
                    as employeeTax,

                /*
                |--------------------------------------------------------------------------
                | LOANS
                |--------------------------------------------------------------------------
                */
                COALESCE(SUM(HdmfLoanAdj),0)
                    as HdmfLoanAdj,

                COALESCE(SUM(sssSalaryLoanAdj),0)
                    as sssSalaryLoanAdj,

                COALESCE(SUM(coopLoan),0)
                    as coopLoan,

                /*
                |--------------------------------------------------------------------------
                | TOTALS
                |--------------------------------------------------------------------------
                */
                COALESCE(SUM(total_deduction),0)
                    as total_deduction,

                COALESCE(SUM(net_pay),0)
                    as net_pay
            ')

            ->first();

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */
        return Pdf::view('pdf.payslip', [

            'payslip' => $payslip,

            'employee' => $employee,

            'ytd' => $ytd,

            'payrollDate' =>
                \Carbon\Carbon::parse(
                    $payslip->payrollDate
                )->format('F d, Y'),
        ])

        ->withBrowsershot(function ($browser) {

            $browser
                ->setChromePath(
                    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
                )
                ->noSandbox();
        })

        ->format('a4')

        ->margins(8, 8, 8, 8)

        ->download(
            'Payslip-'.$payslip->empnum.'.pdf'
        );
    }
}

