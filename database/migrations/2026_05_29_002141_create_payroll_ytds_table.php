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
        Schema::create('payroll_ytds', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cutoff_id');

            $table->string('empnum');

            $table->integer('year');

            $table->decimal('basic_pay', 15, 2)->default(0.00);
            $table->decimal('gross_pay', 15, 2)->default(0.00);
            $table->decimal('total_income', 15, 2)->default(0.00);

            $table->decimal('sss_mpf_employee', 15, 2)->default(0.00);
            $table->decimal('sss_employee', 15, 2)->default(0.00);
            $table->decimal('philhealth_employee', 15, 2)->default(0.00);
            $table->decimal('pagibig_employee', 15, 2)->default(0.00);

            $table->decimal('withholding_tax', 15, 2)->default(0.00);
            $table->decimal('total_deduction', 15, 2)->default(0.00);
            $table->decimal('net_pay', 15, 2)->default(0.00);

            $table->decimal('non_taxable_benefits', 15, 2)->default(0.00);
            $table->decimal('taxable_benefits', 15, 2)->default(0.00);

            $table->timestamps();

            $table->unique(
                ['cutoff_id', 'empnum'],
                'payroll_ytds_cutoff_id_empnum_unique'
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
        Schema::dropIfExists('payroll_ytds');
    }
};
