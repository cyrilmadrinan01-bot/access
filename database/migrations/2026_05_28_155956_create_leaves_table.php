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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();

            $table->string('empnum', 50);

            $table->foreignId('leave_type_id')
                ->constrained('leave_types');

            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('days', 5, 2);
            $table->integer('hours');

            $table->text('reason')->nullable();

            // ENUM replacement
            $table->string('status')->default('Pending');

            $table->timestamps();

            // Indexes
            $table->index('empnum', 'leaves_empnum_index');
            $table->index('leave_type_id');
        });

        /**
         * Optional CHECK constraint for status enum behavior
         */
        DB::statement("
            ALTER TABLE leaves
            ADD CONSTRAINT leaves_status_check
            CHECK (status IN ('Draft', 'Pending', 'Approved', 'Rejected'))
        ");

        /**
         * Optional FK for empnum (requires employees.empnum UNIQUE)
         */
        DB::statement("
            ALTER TABLE leaves
            ADD CONSTRAINT leaves_empnum_foreign
            FOREIGN KEY (empnum)
            REFERENCES employees(empnum)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
