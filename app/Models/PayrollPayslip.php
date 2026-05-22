<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPayslip extends Model
{
    protected $table = 'payroll_payslips';

    protected $fillable = [

        // Reference
        'cutoff_id',
        'empnum',
        'empname',

        // =========================
        // EARNINGS
        // =========================
        'basic_pay',

        'nsdReg',
        'ot',
        'nsdOt',

        'rdReg',
        'rdOt',

        'nsdRdReg',
        'nsdRdOt',

        'lhReg',
        'lhOt',
        'nsdLh',
        'nsdLhOt',

        'shReg',
        'shOt',
        'nsdSh',
        'nsdShOt',

        'lhrdReg',
        'lhrdOt',
        'nsdLhRd',
        'nsdLhRdOt',

        'shrdReg',
        'shrdOt',
        'nsdShRd',
        'nsdShRdOt',

        'dtrAdjustment',
        'otAdjustment',

        'medicalAssistance',
        'retentionBonus',
        'cashGift',
        'ltiCashAwards',

        // =========================
        // BENEFITS
        // =========================
        'uniformClothingAllowance',
        'transpoAllowance',
        'laundryAllowance',
        'medicalCashAllowance',
        'mealOt',
        'mealAllowanceAdj',
        'riceAllowance',

        // =========================
        // DEDUCTIONS
        // =========================
        'employeeTax',
        'sssEmployee',
        'sssMpfEe',
        'philhealthEe',
        'pagibigEe',

        'employeeSavings',
        'HdmfLoanAdj',
        'coopLoan',
        'sssSalaryLoanAdj',
        'taxAdjustment',

        'late',
        'absent',
        'undertime',

        // =========================
        // TOTALS
        // =========================
        'gross_pay',
        'total_benefits',
        'total_income',
        'total_deduction',
        'net_pay',

        // =========================
        // YTD SNAPSHOT
        // =========================
        'ytd_gross',
        'ytd_taxable',
        'ytd_tax',
        'ytd_sss',
        'ytd_philhealth',
        'ytd_pagibig',
        'ytd_net',
    ];

    protected $casts = [
        // ensures numeric safety (VERY important for payroll)
        'basic_pay' => 'float',

        'gross_pay' => 'float',
        'total_benefits' => 'float',
        'total_income' => 'float',
        'total_deduction' => 'float',
        'net_pay' => 'float',

        'ytd_gross' => 'float',
        'ytd_taxable' => 'float',
        'ytd_tax' => 'float',
        'ytd_sss' => 'float',
        'ytd_philhealth' => 'float',
        'ytd_pagibig' => 'float',
        'ytd_net' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes (optional but useful)
    |--------------------------------------------------------------------------
    */

    public function scopeByCutoff($query, $cutoffId)
    {
        return $query->where('cutoff_id', $cutoffId);
    }

    public function scopeByEmployee($query, $empnum)
    {
        return $query->where('empnum', $empnum);
    }
}
