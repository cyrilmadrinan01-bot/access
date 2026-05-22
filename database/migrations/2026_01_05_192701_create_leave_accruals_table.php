<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_accruals', function (Blueprint $table) {
            $table->id();

            // HR reference (business key)
            $table->string('empnum');

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete();

            $table->decimal('amount', 6, 2);

            $table->enum('accrual_type', [
                'Manual',
                'Monthly',
                'Yearly',
                'Adjustment'
            ])->default('Manual');

            $table->string('remarks')->nullable();

            // who performed the action (system/admin)
            $table->foreignId('accrued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // FK to employees.empnum
            $table->foreign('empnum')
                ->references('empnum')
                ->on('employees')
                ->cascadeOnDelete();

            // Optional index (recommended for reports)
            $table->index(['empnum', 'leave_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_accruals');
    }
};
