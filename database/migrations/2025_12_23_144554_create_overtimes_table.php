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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timekeeping_id')
                ->constrained('timekeepings')
                ->cascadeOnDelete();

            $table->string('empnum', 50);

            $table->date('overtimeDate');
            $table->dateTime('startTime');
            $table->dateTime('endTime');

            $table->decimal('hours', 5, 2);

            $table->enum('status', [
                'Pending',
                'Approved',
                'Rejected',
                'Deleted',
            ])->default('Pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
