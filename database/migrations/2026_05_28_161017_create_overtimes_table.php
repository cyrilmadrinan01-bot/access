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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('timekeeping_id')
                ->constrained('timekeepings')
                ->cascadeOnDelete();

            $table->foreignId('payroll_cut_off_id')
                ->nullable()
                ->constrained('payroll_cut_offs')
                ->nullOnDelete();

            $table->string('empnum', 50);

            $table->date('overtimeDate');

            $table->dateTime('startTime');
            $table->dateTime('endTime');

            $table->decimal('hours', 5, 2);
            $table->decimal('nsd', 5, 2)->default(0.00);

            $table->string('status')->default('Pending');

            $table->boolean('is_adjustment')->default(false);

            $table->string('reasons')->nullable();
            $table->string('reject_reason')->nullable();

            $table->string('approved_by')->nullable();
            $table->string('updated_by')->nullable();

            $table->bigInteger('adjusted_from_id')->nullable();

            $table->integer('meal_eligible')->nullable();

            $table->timestamps();

            // indexes
            $table->index('timekeeping_id');
            $table->index('payroll_cut_off_id');
            $table->index('empnum');
            $table->index('status');
        });

        /**
         * ENUM replacement constraint (PostgreSQL-safe)
         */
        DB::statement("
            ALTER TABLE overtimes
            ADD CONSTRAINT overtimes_status_check
            CHECK (status IN ('Pending', 'Approved', 'Rejected', 'Deleted'))
        ");

        /**
         * FK constraints for empnum (requires users.empnum UNIQUE)
         */
        DB::statement("
            ALTER TABLE overtimes
            ADD CONSTRAINT overtimes_approved_by_foreign
            FOREIGN KEY (approved_by)
            REFERENCES users(empnum)
            ON DELETE SET NULL
        ");

        DB::statement("
            ALTER TABLE overtimes
            ADD CONSTRAINT overtimes_updated_by_foreign
            FOREIGN KEY (updated_by)
            REFERENCES users(empnum)
            ON DELETE SET NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
