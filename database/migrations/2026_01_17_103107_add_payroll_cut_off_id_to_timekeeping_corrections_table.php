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
        Schema::table('timekeeping_corrections', function (Blueprint $table) {
            $table->foreignId('payroll_cut_off_id')
                 ->nullable()
                ->after('timekeeping_id')
                ->constrained('payroll_cut_offs')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timekeeping_corrections', function (Blueprint $table) {
            $table->dropForeign(['payroll_cut_off_id']);
            $table->dropColumn('payroll_cut_off_id');
        });
    }
};
