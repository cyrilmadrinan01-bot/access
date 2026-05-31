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
        Schema::create('timekeepings', function (Blueprint $table) {
            $table->id();

            $table->string('empnum');

            $table->date('dated');
            $table->date('payrollDate');

            $table->string('dayType');
            $table->string('shiftCode');

            $table->dateTime('timeIn')->nullable();
            $table->dateTime('timeOut')->nullable();

            $table->string('correctedShiftCode')->nullable();
            $table->dateTime('correctedTimeIn')->nullable();
            $table->dateTime('correctedTimeOut')->nullable();

            $table->string('typeCode')->nullable();

            $table->decimal('regHours', 5, 2)->default(0.00);
            $table->decimal('overtime', 5, 2)->default(0.00);
            $table->decimal('nsd', 5, 2)->default(0.00);
            $table->decimal('late', 5, 2)->default(0.00);
            $table->decimal('undertime', 5, 2)->default(0.00);

            $table->string('leaveCode')->nullable();
            $table->string('flagStatus')->default('0.00');

            $table->decimal('hoursWorked', 5, 2)->nullable();

            $table->string('source')->nullable();

            $table->time('shiftStart');
            $table->time('shiftEnd');

            $table->string('reason')->nullable();
            $table->string('otherReason')->nullable();

            $table->json('segments')->nullable();

            $table->foreignId('shiftcode_id')
                ->nullable()
                ->constrained('shiftcodes');

            $table->foreignId('corrected_shiftcode_id')
                ->nullable()
                ->constrained('shiftcodes');

            $table->foreignId('reason_id')
                ->nullable()
                ->constrained('reasons');

            $table->foreignId('leave_id')
                ->nullable()
                ->constrained('leaves')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['empnum', 'dated'], 'timekeepings_empnum_dated_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timekeepings');
    }
};
