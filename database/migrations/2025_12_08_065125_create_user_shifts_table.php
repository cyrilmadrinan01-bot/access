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
        Schema::create('user_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('empnum');
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unsignedBigInteger('shiftcode_id');
            $table->foreign('shiftcode_id')->references('id')->on('shiftcodes')->onDelete('cascade');
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->string('changed_by')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shifts');
    }
};
