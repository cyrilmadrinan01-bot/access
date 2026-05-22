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
        Schema::table('overtimes', function (Blueprint $table) {
            $table->foreignId('payroll_cut_off_id')
                  ->nullable() // optional: allow nulls for old records
                  ->after('timekeeping_id') // place it after timekeeping_id
                  ->constrained('payroll_cut_offs') // foreign key
                  ->cascadeOnDelete(); // delete OT if cutoff deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropForeign(['payroll_cut_off_id']);
            $table->dropColumn('payroll_cut_off_id');
        });
    }
};
