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

            $table->string('empnum');
            $table->integer('year');

            $table->decimal('gross_income', 15, 2)->default(0);
            $table->decimal('taxable_income', 15, 2)->default(0);
            $table->decimal('withholding_tax', 15, 2)->default(0);

            $table->decimal('sss_employee', 15, 2)->default(0);
            $table->decimal('philhealth_employee', 15, 2)->default(0);
            $table->decimal('pagibig_employee', 15, 2)->default(0);

            $table->decimal('net_pay', 15, 2)->default(0);

            $table->timestamps();

            $table->unique(['empnum', 'year']);
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
