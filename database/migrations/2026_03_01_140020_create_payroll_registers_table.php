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
        Schema::create('payroll_registers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cutoff_id');
            $table->string('empnum');
            $table->string('empname');
            $table->string('accountNumber');
            $table->string('payrollType');
            $table->string('factor');

            $table->decimal('annual_salary', 15, 2);
            $table->decimal('monthly_rate', 15, 2);
            $table->decimal('hourly_rate', 15, 2);
            $table->decimal('basic_pay', 15, 2);
            $table->decimal('late', 15, 2);
            $table->decimal('absent', 15, 2);
            $table->decimal('nsdReg', 15, 2);
            $table->decimal('ot', 15, 2);
            $table->decimal('otAfterReg', 15, 2);
            $table->decimal('nsdOt', 15, 2);
            $table->decimal('rdReg', 15, 2);
            $table->decimal('rdOt', 15, 2);
            $table->decimal('rdOtAfterReg', 15, 2);
            $table->decimal('nsdRdReg', 15, 2);
            $table->decimal('nsdRdOt', 15, 2);
            $table->decimal('lhReg', 15, 2);
            $table->decimal('lhOt', 15, 2);
            $table->decimal('lhOtAfterReg', 15, 2);
            $table->decimal('nsdLh', 15, 2);
            $table->decimal('nsdLhOt', 15, 2);
            $table->decimal('shReg', 15, 2);
            $table->decimal('shOt', 15, 2);
            $table->decimal('shOtAfterReg', 15, 2);
            $table->decimal('nsdSh', 15, 2);
            $table->decimal('nsdShOt', 15, 2);
            
            $table->decimal('lhrdReg', 15, 2);
            $table->decimal('lhrdOt', 15, 2);
            $table->decimal('lhrdOtAfterReg', 15, 2);
            $table->decimal('nsdLhRd', 15, 2);
            $table->decimal('nsdLhRdOt', 15, 2);
            
            $table->decimal('shrdReg', 15, 2);
            $table->decimal('shrdOt', 15, 2);
            $table->decimal('shrdOtAfterReg', 15, 2);
            $table->decimal('nsdShRd', 15, 2);
            $table->decimal('nsdShRdOt', 15, 2);

            $table->decimal('dtrAdjustment', 15, 2);
            $table->decimal('otAdjustment', 15, 2);

            $table->decimal('mealAllowanceAdj', 15, 2);
            $table->decimal('uniformClothingAllowance', 15, 2);
            $table->decimal('transpoAllowance', 15, 2);
            $table->decimal('laundryAllowance', 15, 2);
            $table->decimal('busMarshallAllowance', 15, 2);
            $table->decimal('monthlyHomeSubsidy', 15, 2);
            $table->decimal('cashGift', 15, 2);
            $table->decimal('ltiCashAwards', 15, 2);
            $table->decimal('gasAllowance', 15, 2);
            $table->decimal('medicalAssistance', 15, 2);
            $table->decimal('medicalCashAllowance', 15, 2);
            $table->decimal('retentionBonus', 15, 2);
            $table->decimal('mealOt', 15, 2);
            $table->decimal('gross', 15, 2);

            $table->decimal('employeeTax', 15, 2);
            $table->decimal('sssEmployee', 15, 2);
            $table->decimal('sssMpfEe', 15, 2);
            $table->decimal('sssEmployer', 15, 2);
            $table->decimal('sssMpfEr', 15, 2);
            $table->decimal('sssEc', 15, 2);
            $table->decimal('philhealthEe', 15, 2);
            $table->decimal('philhealthEr', 15, 2);
            $table->decimal('pagibigEe', 15, 2);
            $table->decimal('pagibigEr', 15, 2);
            $table->decimal('employeeSavings', 15, 2);
            $table->decimal('HdmfLoanAdj', 15, 2);
            $table->decimal('coopLoan', 15, 2);
            $table->decimal('sssSalaryLoanAdj', 15, 2);
            $table->decimal('taxAdjustment', 15, 2);
            $table->decimal('totalDeduction', 15, 2);
            $table->decimal('net', 15, 2);
            $table->decimal('atm', 15, 2);

            $table->timestamps();

            $table->unique(['cutoff_id', 'empnum']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_registers');
    }
};
