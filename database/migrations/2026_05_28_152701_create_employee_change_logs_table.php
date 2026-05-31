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
        Schema::create('employee_change_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->string('domain');
            $table->string('field');

            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            $table->date('effective_date');

            $table->boolean('is_future');

            $table->foreignId('changed_by')
                ->constrained('users');

            $table->timestamps();

             // Indexes for performance
            $table->index('employee_id');
            $table->index('changed_by');
            $table->index(['employee_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_change_logs');
    }
};
