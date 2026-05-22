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
        Schema::table('timekeeping_records', function (Blueprint $table) {
            $table->unsignedBigInteger('correction_id')->nullable()->after('id');

            $table->foreign('correction_id')
                  ->references('id')
                  ->on('timekeeping_corrections')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timekeeping_records', function (Blueprint $table) {
            $table->dropForeign(['correction_id']);
            $table->dropColumn('correction_id');
        });
    }
};
