<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->string('type');

            $table->date('start_date');
            $table->date('end_date');

            $table->string('status')->default('pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('employees');

            $table->timestamps();

            // Indexes
            $table->index('employee_id');
            $table->index('approved_by');
        });

         DB::statement("
            ALTER TABLE leave_requests
            ADD CONSTRAINT leave_requests_status_check
            CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
