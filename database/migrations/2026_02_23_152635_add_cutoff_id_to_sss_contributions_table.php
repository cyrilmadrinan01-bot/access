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
        Schema::table('sss_contributions', function (Blueprint $table) {
            $table->unsignedBigInteger('cutoff_id')->after('id');

            $table->foreign('cutoff_id')
                ->references('id')
                ->on('payroll_cut_offs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sss_contributions', function (Blueprint $table) {
            $table->dropForeign(['cutoff_id']);
            $table->dropColumn('cutoff_id');
        });
    }
};
