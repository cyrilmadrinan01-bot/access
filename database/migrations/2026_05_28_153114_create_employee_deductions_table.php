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
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cutoff_id')
                ->constrained('payroll_cut_offs')
                ->cascadeOnDelete();

            $table->string('empnum');

            $table->string('empname')->nullable();

            $table->string('deduction_type');

            $table->decimal('amount', 15, 2);

            $table->boolean('is_pre_tax')->default(false);

            $table->timestamp('uploaded_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('cutoff_id');
            $table->index('empnum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
