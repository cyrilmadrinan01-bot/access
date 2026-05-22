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
        Schema::create('employee_terminations', function (Blueprint $table) {
            $table->id();

            $table->string('employee_id');
            $table->string('empnum');
            $table->string('employee_name');

            $table->date('termination_date');
            $table->string('termination_reason');
            $table->date('access_termination_date')->nullable();

            $table->timestamps();

            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_terminations');
    }
};
