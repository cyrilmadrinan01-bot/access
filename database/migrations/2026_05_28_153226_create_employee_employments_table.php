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
        Schema::create('employee_employments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->string('company_code');
            $table->string('department_code');
            $table->string('job_code');

            $table->string('manager_empnum');

            $table->string('status')->default('ACTIVE');

            $table->date('hire_date');
            $table->date('termination_date')->nullable();

            $table->date('effective_start');
            $table->date('effective_end')->nullable();

            $table->string('business_unit')->nullable();
            $table->string('division')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('channel_code')->nullable();
            $table->string('line_code')->nullable();
            $table->string('project')->nullable();
            $table->string('account_code')->nullable();
            $table->string('intercompany')->nullable();
            $table->string('regular_temp')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'effective_start'], 'idx_employee_effective');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_employments');
    }
};
