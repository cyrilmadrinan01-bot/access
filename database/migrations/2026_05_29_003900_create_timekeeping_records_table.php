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
        Schema::create('timekeeping_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('correction_id')
                ->nullable()
                ->constrained('timekeeping_corrections')
                ->nullOnDelete();

            $table->foreignId('timekeeping_id')
                ->constrained('timekeepings')
                ->cascadeOnDelete();

            $table->string('empnum', 50);

            $table->date('dated');
            $table->date('payrollDate');

            $table->string('dayType', 20);
            $table->string('shiftCode', 20);

            $table->dateTime('timeIn')->nullable();
            $table->dateTime('timeOut')->nullable();

            $table->string('typeCode', 20);

            $table->decimal('regHours', 5, 2)->default(0.00);
            $table->decimal('overtime', 5, 2)->default(0.00);
            $table->decimal('nsd', 5, 2)->default(0.00);
            $table->decimal('late', 5, 2)->default(0.00);
            $table->decimal('undertime', 5, 2)->default(0.00);

            $table->string('leaveCode', 20)->nullable();
            $table->string('flagStatus', 20)->nullable();

            $table->decimal('hoursWorked', 5, 2)->default(0.00);

            $table->string('source', 50)->nullable();

            $table->time('shiftStart');
            $table->time('shiftEnd');

            $table->string('reason')->nullable();
            $table->string('otherReason')->nullable();

            $table->string('appStatusId', 20)->nullable();
            $table->string('flag', 20)->nullable();

            $table->timestamps();

            $table->index('timekeeping_id');
            $table->index('correction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timekeeping_records');
    }
};
