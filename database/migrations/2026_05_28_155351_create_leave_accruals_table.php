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
        Schema::create('leave_accruals', function (Blueprint $table) {
            $table->id();

            $table->string('empnum');

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete();

            $table->decimal('amount', 6, 2);

            // ENUM replacement for PostgreSQL
            $table->string('accrual_type')->default('Manual');

            $table->string('remarks')->nullable();

            $table->foreignId('accrued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

             // Indexes
            $table->index(['empnum', 'leave_type_id'], 'leave_accruals_empnum_leave_type_id_index');
            $table->index('leave_type_id');
            $table->index('accrued_by');
        });

        /**
         * Optional CHECK constraint for enum behavior
         */
        DB::statement("
            ALTER TABLE leave_accruals
            ADD CONSTRAINT leave_accruals_accrual_type_check
            CHECK (accrual_type IN ('Manual', 'Monthly', 'Yearly', 'Adjustment'))
        ");

        /**
         * Foreign key for empnum (requires employees.empnum UNIQUE)
         */
        DB::statement("
            ALTER TABLE leave_accruals
            ADD CONSTRAINT leave_accruals_empnum_foreign
            FOREIGN KEY (empnum)
            REFERENCES employees(empnum)
            ON DELETE CASCADE
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_accruals');
    }
};
