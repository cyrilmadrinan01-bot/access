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
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->string('shift_code');

            $table->boolean('is_rotating')->default(false);

            $table->date('effective_start');
            $table->date('effective_end')->nullable();

            $table->timestamps();

            // Indexes for fast HR scheduling queries
            $table->index('employee_id');
            $table->index(['employee_id', 'effective_start'], 'employee_shifts_effective_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_shifts');
    }
};
