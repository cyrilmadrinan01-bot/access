<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_payslips', function (Blueprint $table) {
            $table->id();

             // Reference
            $table->unsignedBigInteger('cutoff_id');
            $table->string('empnum');
            $table->string('empname');

            // =========================
            // EARNINGS BREAKDOWN
            // =========================
            $table->decimal('basic_pay', 15, 2)->default(0);

            $table->decimal('nsdReg', 15, 2)->default(0);
            $table->decimal('ot', 15, 2)->default(0);
            $table->decimal('nsdOt', 15, 2)->default(0);

            $table->decimal('rdReg', 15, 2)->default(0);
            $table->decimal('rdOt', 15, 2)->default(0);

            $table->decimal('nsdRdReg', 15, 2)->default(0);
            $table->decimal('nsdRdOt', 15, 2)->default(0);

            $table->decimal('lhReg', 15, 2)->default(0);
            $table->decimal('lhOt', 15, 2)->default(0);
            $table->decimal('nsdLh', 15, 2)->default(0);
            $table->decimal('nsdLhOt', 15, 2)->default(0);

            $table->decimal('shReg', 15, 2)->default(0);
            $table->decimal('shOt', 15, 2)->default(0);
            $table->decimal('nsdSh', 15, 2)->default(0);
            $table->decimal('nsdShOt', 15, 2)->default(0);

            $table->decimal('lhrdReg', 15, 2)->default(0);
            $table->decimal('lhrdOt', 15, 2)->default(0);
            $table->decimal('nsdLhRd', 15, 2)->default(0);
            $table->decimal('nsdLhRdOt', 15, 2)->default(0);

            $table->decimal('shrdReg', 15, 2)->default(0);
            $table->decimal('shrdOt', 15, 2)->default(0);
            $table->decimal('nsdShRd', 15, 2)->default(0);
            $table->decimal('nsdShRdOt', 15, 2)->default(0);

            $table->decimal('dtrAdjustment', 15, 2)->default(0);
            $table->decimal('otAdjustment', 15, 2)->default(0);

            $table->decimal('medicalAssistance', 15, 2)->default(0);
            $table->decimal('retentionBonus', 15, 2)->default(0);
            $table->decimal('cashGift', 15, 2)->default(0);
            $table->decimal('ltiCashAwards', 15, 2)->default(0);

            // =========================
            // BENEFITS
            // =========================
            $table->decimal('uniformClothingAllowance', 15, 2)->default(0);
            $table->decimal('transpoAllowance', 15, 2)->default(0);
            $table->decimal('laundryAllowance', 15, 2)->default(0);
            $table->decimal('medicalCashAllowance', 15, 2)->default(0);
            $table->decimal('mealOt', 15, 2)->default(0);
            $table->decimal('mealAllowanceAdj', 15, 2)->default(0);
            $table->decimal('riceAllowance', 15, 2)->default(0);

            // =========================
            // DEDUCTIONS
            // =========================
            $table->decimal('employeeTax', 15, 2)->default(0);
            $table->decimal('sssEmployee', 15, 2)->default(0);
            $table->decimal('sssMpfEe', 15, 2)->default(0);
            $table->decimal('philhealthEe', 15, 2)->default(0);
            $table->decimal('pagibigEe', 15, 2)->default(0);

            $table->decimal('employeeSavings', 15, 2)->default(0);
            $table->decimal('HdmfLoanAdj', 15, 2)->default(0);
            $table->decimal('coopLoan', 15, 2)->default(0);
            $table->decimal('sssSalaryLoanAdj', 15, 2)->default(0);
            $table->decimal('taxAdjustment', 15, 2)->default(0);

            $table->decimal('late', 15, 2)->default(0);
            $table->decimal('absent', 15, 2)->default(0);
            $table->decimal('undertime', 15, 2)->default(0);

            // =========================
            // TOTALS
            // =========================
            $table->decimal('gross_pay', 15, 2)->default(0);
            $table->decimal('total_benefits', 15, 2)->default(0);
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);

            // =========================
            // YTD SNAPSHOT (IMPORTANT)
            // =========================
            $table->decimal('ytd_gross', 15, 2)->default(0);
            $table->decimal('ytd_taxable', 15, 2)->default(0);
            $table->decimal('ytd_tax', 15, 2)->default(0);
            $table->decimal('ytd_sss', 15, 2)->default(0);
            $table->decimal('ytd_philhealth', 15, 2)->default(0);
            $table->decimal('ytd_pagibig', 15, 2)->default(0);
            $table->decimal('ytd_net', 15, 2)->default(0);

            // optional audit
            $table->timestamps();

            // IMPORTANT: one payslip per employee per cutoff
            $table->unique(['cutoff_id', 'empnum']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_payslips');
    }
};
