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
        Schema::create('user_shifts', function (Blueprint $table) {
            $table->id();

            $table->string('empnum');

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('shiftcode_id')
                ->constrained('shiftcodes')
                ->cascadeOnDelete();

            $table->date('effective_date');

            $table->date('end_date')->nullable();

            $table->string('changed_by')->nullable();

            $table->string('reason')->nullable();

            $table->timestamps();

            $table->unique(
                ['employee_id', 'effective_date'],
                'user_shifts_employee_id_effective_date_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shifts');
    }
};
