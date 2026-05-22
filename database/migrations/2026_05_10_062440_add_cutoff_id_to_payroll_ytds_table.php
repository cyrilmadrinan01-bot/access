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
        Schema::table('payroll_ytds', function (Blueprint $table) {
            $table->unsignedBigInteger('cutoff_id')
                  ->after('empnum');

            $table->index(['empnum', 'year']);
            $table->index('cutoff_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_ytds', function (Blueprint $table) {
            $table->dropIndex(['empnum', 'year']);
            $table->dropIndex(['cutoff_id']);
            $table->dropColumn('cutoff_id');
        });
    }
};
