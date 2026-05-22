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
        Schema::create('payroll_cut_offs', function (Blueprint $table) {
            $table->id();
            $table->date('cutOffStart');
            $table->date('cutOffEnd');
            $table->date('payrollDate');

            $table->enum('current', ['Yes', 'No'])->default('No');

            $table->date('lockDate')->nullable();
            $table->time('lockTime')->nullable();
            $table->timestamps();

            $table->index(['cutOffStart', 'cutOffEnd']);
            $table->index('current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_cut_offs');
    }
};
