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

            $table->unsignedBigInteger('cutoff_id');

            $table->string('empnum');
            $table->string('empname');

            // Earnings
            $table->decimal('basic_pay', 15, 2)->default(0.00);
            $table->decimal('nsdReg', 15, 2)->default(0.00);
            $table->decimal('ot', 15, 2)->default(0.00);
            $table->decimal('nsdOt', 15, 2)->default(0.00);
            $table->decimal('rdReg', 15, 2)->default(0.00);
            $table->decimal('rdOt', 15, 2)->default(0.00);
            $table->decimal('nsdRdReg', 15, 2)->default(0.00);
            $table->decimal('nsdRdOt', 15, 2)->default(0.00);

            $table->decimal('lhReg', 15, 2)->default(0.00);
            $table->decimal('lhOt', 15, 2)->default(0.00);
            $table->decimal('nsdLh', 15, 2)->default(0.00);
            $table->decimal('nsdLhOt', 15, 2)->default(0.00);

            $table->decimal('shReg', 15, 2)->default(0.00);
            $table->decimal('shOt', 15, 2)->default(0.00);
            $table->decimal('nsdSh', 15, 2)->default(0.00);
            $table->decimal('nsdShOt', 15, 2)->default(0.00);

            $table->decimal('lhrdReg', 15, 2)->default(0.00);
            $table->decimal('lhrdOt', 15, 2)->default(0.00);
            $table->decimal('nsdLhRd', 15, 2)->default(0.00);
            $table->decimal('nsdLhRdOt', 15, 2)->default(0.00);

            $table->decimal('shrdReg', 15, 2)->default(0.00);
            $table->decimal('shrdOt', 15, 2)->default(0.00);
            $table->decimal('nsdShRd', 15, 2)->default(0.00);
            $table->decimal('nsdShRdOt', 15, 2)->default(0.00);

            // Adjustments / Benefits
            $table->decimal('dtrAdjustment', 15, 2)->default(0.00);
            $table->decimal('otAdjustment', 15, 2)->default(0.00);
            $table->decimal('medicalAssistance', 15, 2)->default(0.00);
            $table->decimal('retentionBonus', 15, 2)->default(0.00);
            $table->decimal('cashGift', 15, 2)->default(0.00);
            $table->decimal('ltiCashAwards', 15, 2)->default(0.00);
            $table->decimal('uniformClothingAllowance', 15, 2)->default(0.00);
            $table->decimal('transpoAllowance', 15, 2)->default(0.00);
            $table->decimal('laundryAllowance', 15, 2)->default(0.00);
            $table->decimal('medicalCashAllowance', 15, 2)->default(0.00);
            $table->decimal('mealOt', 15, 2)->default(0.00);
            $table->decimal('mealAllowanceAdj', 15, 2)->default(0.00);
            $table->decimal('riceAllowance', 15, 2)->default(0.00);

            // Deductions
            $table->decimal('employeeTax', 15, 2)->default(0.00);
            $table->decimal('sssEmployee', 15, 2)->default(0.00);
            $table->decimal('sssMpfEe', 15, 2)->default(0.00);
            $table->decimal('philhealthEe', 15, 2)->default(0.00);
            $table->decimal('pagibigEe', 15, 2)->default(0.00);
            $table->decimal('employeeSavings', 15, 2)->default(0.00);
            $table->decimal('HdmfLoanAdj', 15, 2)->default(0.00);
            $table->decimal('coopLoan', 15, 2)->default(0.00);
            $table->decimal('sssSalaryLoanAdj', 15, 2)->default(0.00);
            $table->decimal('taxAdjustment', 15, 2)->default(0.00);

            // Attendance deductions
            $table->decimal('late', 15, 2)->default(0.00);
            $table->decimal('absent', 15, 2)->default(0.00);
            $table->decimal('undertime', 15, 2)->default(0.00);

            // Totals
            $table->decimal('gross_pay', 15, 2)->default(0.00);
            $table->decimal('total_benefits', 15, 2)->default(0.00);
            $table->decimal('total_income', 15, 2)->default(0.00);
            $table->decimal('total_deduction', 15, 2)->default(0.00);
            $table->decimal('net_pay', 15, 2)->default(0.00);

            // YTD
            $table->decimal('ytd_gross', 15, 2)->default(0.00);
            $table->decimal('ytd_taxable', 15, 2)->default(0.00);
            $table->decimal('ytd_tax', 15, 2)->default(0.00);
            $table->decimal('ytd_sss', 15, 2)->default(0.00);
            $table->decimal('ytd_philhealth', 15, 2)->default(0.00);
            $table->decimal('ytd_pagibig', 15, 2)->default(0.00);
            $table->decimal('ytd_net', 15, 2)->default(0.00);

            $table->timestamps();

            $table->unique(
                ['cutoff_id', 'empnum'],
                'payroll_payslips_cutoff_id_empnum_unique'
            );

            // Optional FK
             $table->foreign('cutoff_id')
                 ->references('id')
                 ->on('payroll_cut_offs')
                 ->cascadeOnDelete();
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
