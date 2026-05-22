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
        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();

            // HR business key
            $table->string('empnum');

            $table->foreignId('leave_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('balance', 6, 2)->default(0);

            $table->timestamps();

            // Unique balance per employee + leave type
            $table->unique(['empnum', 'leave_type_id']);

            // FK to employees.empnum
            $table->foreign('empnum')
                ->references('empnum')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_balances');
    }
};
