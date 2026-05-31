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
        Schema::create('employee_org_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->foreignId('org_unit_id')
                ->constrained('org_units');

            $table->date('effective_start');
            $table->date('effective_end')->nullable();

            $table->timestamps();

            // Indexes for fast HR/org queries
            $table->index('employee_id');
            $table->index('org_unit_id');
            $table->index(['employee_id', 'effective_start'], 'emp_org_effective_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_org_assignments');
    }
};
