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
            $table->datetime('timeIn')->nullable();
            $table->datetime('timeOut')->nullable();
            $table->string('correctedShiftCode')->nullable();
            $table->datetime('correctedTimeIn')->nullable();
            $table->datetime('correctedTimeOut')->nullable();
            $table->string('typeCode')->nullable();
            $table->decimal('regHours', 5, 2)->nullable();
            $table->decimal('overtime', 5, 2)->nullable();
            $table->decimal('nsd', 5, 2)->nullable();
            $table->decimal('late', 5, 2)->nullable();
            $table->decimal('undertime', 5, 2)->nullable();
            $table->string('leaveCode')->nullable();
            $table->string('flagStatus')->nullable();
            $table->decimal('hoursWorked', 5, 2)->nullable();
            $table->string('source')->nullable();
            $table->time('shiftStart');
            $table->time('shiftEnd');
            $table->string('reason')->nullable();
            $table->string('otherReason')->nullable();
            $table->json('segments')->nullable(); 
            $table->foreignId('shiftcode_id')->nullable()->constrained('shiftcodes');
            $table->foreignId('corrected_shiftcode_id')->nullable()->constrained('shiftcodes');

            $table->timestamps();

            $table->unique(['empnum', 'dated']);
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
