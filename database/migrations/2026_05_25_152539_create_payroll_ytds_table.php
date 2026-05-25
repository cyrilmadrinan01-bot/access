<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_ytds', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('cutoff_id');

            $table->string('empnum');

            $table->integer('year');

            /*
            |--------------------------------------------------------------------------
            | EARNINGS
            |--------------------------------------------------------------------------
            */
            $table->decimal('basic_pay', 15, 2)->default(0);

            $table->decimal('gross_pay', 15, 2)->default(0);

            $table->decimal('total_income', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | CONTRIBUTIONS
            |--------------------------------------------------------------------------
            */
            $table->decimal('sss_mpf_employee', 15, 2)->default(0);

            $table->decimal('sss_employee', 15, 2)->default(0);

            $table->decimal('philhealth_employee', 15, 2)->default(0);

            $table->decimal('pagibig_employee', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | TAX
            |--------------------------------------------------------------------------
            */
            $table->decimal('withholding_tax', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | DEDUCTIONS
            |--------------------------------------------------------------------------
            */
            $table->decimal('total_deduction', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | NET PAY
            |--------------------------------------------------------------------------
            */
            $table->decimal('net_pay', 15, 2)->default(0);

            $table->timestamps();

            $table->unique([
                'cutoff_id',
                'empnum',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_ytds');
    }
};
