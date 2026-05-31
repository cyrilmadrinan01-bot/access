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
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('leave_id')
                ->constrained('leaves')
                ->cascadeOnDelete();

            $table->foreignId('approver_id')
                ->constrained('users');

            $table->string('status')->default('pending');

            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('leave_id');
            $table->index('approver_id');
        });

        /**
         * Optional CHECK constraint for status values
         */
        DB::statement("
            ALTER TABLE leave_approvals
            ADD CONSTRAINT leave_approvals_status_check
            CHECK (status IN ('pending', 'approved', 'rejected'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
    }
};
