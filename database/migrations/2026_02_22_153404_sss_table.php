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
        Schema::create('sss_table', function (Blueprint $table) {
            $table->id();
            $table->decimal('salary_min', 15, 2);
            $table->decimal('salary_max', 15, 2);
            $table->decimal('employer_regular', 15, 2);
            $table->decimal('employer_mpf', 15, 2);
            $table->decimal('employer_ec', 15, 2);
            $table->decimal('employer_total', 15, 2);
            $table->decimal('employee_resular', 15, 2);
            $table->decimal('employe_mpf', 15, 2);
            $table->decimal('employee_total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('sss_table');
    }
};
