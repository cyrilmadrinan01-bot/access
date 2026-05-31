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
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->decimal('base_salary', 12, 2);

            $table->string('pay_grade');

            $table->date('effective_start');
            $table->date('effective_end')->nullable();

            $table->string('pay_type')->nullable();

            $table->integer('factor')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('employee_id');
            $table->index('effective_start', 'idx_effective_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
