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
        Schema::create('shiftcodes', function (Blueprint $table) {
            $table->id();

            $table->string('shiftCode');

            $table->time('shiftStart');
            $table->time('shiftEnd');

            $table->decimal('hoursWorked', 8, 2);

            $table->string('withNd');

            $table->time('ndStart');
            $table->time('ndEnd');

            $table->decimal('regHours', 8, 2);

            $table->string('workDay');
            $table->string('restDay');
            $table->string('usShift');
            $table->string('sameDay');

            $table->time('ndCrossDayStart');
            $table->time('ndCrossDayEnd');

            $table->string('rotatingShift');

            $table->string('group');

            $table->boolean('is_active')->default(true);

            $table->decimal('ndHours', 8, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shiftcodes');
    }
};
