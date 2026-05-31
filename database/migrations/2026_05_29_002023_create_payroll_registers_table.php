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

            $table->string('accountNumber')->nullable();
            $table->string('payrollType')->nullable();
            $table->string('factor')->nullable();

            // Salary Info
            $table->decimal('annual_salary', 15, 2)->nullable();
            $table->decimal('monthly_rate', 15, 2)->nullable();
            $table->decimal('hourly_rate', 15, 2)->nullable();

            // Earnings
            $table->decimal('basic_pay', 15, 2)->nullable();
            $table->decimal('late', 15, 2)->nullable();
            $table->decimal('absent', 15, 2)->nullable();
            $table->decimal('undertime', 15, 2)->nullable();

            $table->decimal('nsdReg', 15, 2)->nullable();
            $table->decimal('ot', 15, 2)->nullable();
            $table->decimal('otAfterReg', 15, 2)->nullable();
            $table->decimal('nsdOt', 15, 2)->nullable();

            $table->decimal('rdReg', 15, 2)->nullable();
            $table->decimal('rdOt', 15, 2)->nullable();
            $table->decimal('rdOtAfterReg', 15, 2)->nullable();
            $table->decimal('nsdRdReg', 15, 2)->nullable();
            $table->decimal('nsdRdOt', 15, 2)->nullable();

            $table->decimal('lhReg', 15, 2)->nullable();
            $table->decimal('lhOt', 15, 2)->nullable();
            $table->decimal('lhOtAfterReg', 15, 2)->nullable();
            $table->decimal('nsdLh', 15, 2)->nullable();
            $table->decimal('nsdLhOt', 15, 2)->nullable();

            $table->decimal('shReg', 15, 2)->nullable();
            $table->decimal('shOt', 15, 2)->nullable();
            $table->decimal('shOtAfterReg', 15, 2)->nullable();
            $table->decimal('nsdSh', 15, 2)->nullable();
            $table->decimal('nsdShOt', 15, 2)->nullable();

            $table->decimal('lhrdReg', 15, 2)->nullable();
            $table->decimal('lhrdOt', 15, 2)->nullable();
            $table->decimal('lhrdOtAfterReg', 15, 2)->nullable();
            $table->decimal('nsdLhRd', 15, 2)->nullable();
            $table->decimal('nsdLhRdOt', 15, 2)->nullable();

            $table->decimal('shrdReg', 15, 2)->nullable();
            $table->decimal('shrdOt', 15, 2)->nullable();
            $table->decimal('shrdOtAfterReg', 15, 2)->nullable();
            $table->decimal('nsdShRd', 15, 2)->nullable();
            $table->decimal('nsdShRdOt', 15, 2)->nullable();

            // Adjustments / Benefits
            $table->decimal('dtrAdjustment', 15, 2)->nullable();
            $table->decimal('otAdjustment', 15, 2)->nullable();
            $table->decimal('mealAllowanceAdj', 15, 2)->nullable();

            $table->decimal('uniformClothingAllowance', 15, 2)->nullable();
            $table->decimal('transpoAllowance', 15, 2)->nullable();
            $table->decimal('laundryAllowance', 15, 2)->nullable();
            $table->decimal('busMarshallAllowance', 15, 2)->nullable();
            $table->decimal('monthlyHomeSubsidy', 15, 2)->nullable();

            $table->decimal('cashGift', 15, 2)->nullable();
            $table->decimal('ltiCashAwards', 15, 2)->nullable();
            $table->decimal('gasAllowance', 15, 2)->nullable();
            $table->decimal('medicalAssistance', 15, 2)->nullable();
            $table->decimal('medicalCashAllowance', 15, 2)->nullable();
            $table->decimal('retentionBonus', 15, 2)->nullable();
            $table->decimal('mealOt', 15, 2)->nullable();
            $table->decimal('riceAllowance', 15, 2)->nullable();

            $table->decimal('thirteenthMonth', 15, 2)->default(0.00);
            $table->decimal('nonTaxableBenefits', 15, 2)->default(0.00);
            $table->decimal('taxableBenefits', 15, 2)->default(0.00);

            // Government Contributions / Deductions
            $table->decimal('employeeTax', 15, 2)->nullable();

            $table->decimal('sssEmployee', 15, 2)->nullable();
            $table->decimal('sssMpfEe', 15, 2)->nullable();
            $table->decimal('sssEmployer', 15, 2)->nullable();
            $table->decimal('sssMpfEr', 15, 2)->nullable();
            $table->decimal('sssEc', 15, 2)->nullable();

            $table->decimal('philhealthEe', 15, 2)->nullable();
            $table->decimal('philhealthEr', 15, 2)->nullable();

            $table->decimal('pagibigEe', 15, 2)->nullable();
            $table->decimal('pagibigEr', 15, 2)->nullable();

            // Other Deductions
            $table->decimal('employeeSavings', 15, 2)->nullable();
            $table->decimal('HdmfLoanAdj', 15, 2)->nullable();
            $table->decimal('coopLoan', 15, 2)->nullable();
            $table->decimal('sssSalaryLoanAdj', 15, 2)->nullable();
            $table->decimal('taxAdjustment', 15, 2)->nullable();

            // Totals
            $table->decimal('gross', 15, 2)->nullable();
            $table->decimal('totalDeduction', 15, 2)->nullable();
            $table->decimal('net', 15, 2)->nullable();
            $table->decimal('atm', 15, 2)->nullable();

            $table->timestamps();

             $table->unique(
                ['cutoff_id', 'empnum'],
                'payroll_registers_cutoff_id_empnum_unique'
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
        Schema::dropIfExists('payroll_registers');
    }
};
