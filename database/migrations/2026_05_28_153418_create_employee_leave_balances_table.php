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

            $table->string('empnum');

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete();

            $table->decimal('balance', 6, 2)->default(0.00);

            $table->timestamps();

            // Unique constraint (employee + leave type)
            $table->unique(['empnum', 'leave_type_id'], 'employee_leave_balances_empnum_leave_type_id_unique');

            // Indexes
            $table->index('leave_type_id');
            $table->index('empnum');
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
