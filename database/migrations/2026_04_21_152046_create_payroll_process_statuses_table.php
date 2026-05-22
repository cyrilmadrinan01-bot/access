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
        Schema::create('payroll_process_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutoff_id')->constrained()->cascadeOnDelete();
            $table->string('step'); // timekeeping, other_income, etc.
            $table->string('status')->default('pending'); // pending | completed | skipped
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['cutoff_id', 'step']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_process_statuses');
    }
};
