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
            $table->string('reasons')->after('status')->nullable();
            $table->string('reject_reason')->after('reasons')->nullable();
            $table->string('approved_by')->after('reject_reason')->nullable();
            $table->string('updated_by')->after('approved_by')->nullable();

            $table->foreign('approved_by')->references('empnum')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('empnum')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn([
                'reasons',
                'reject_reason',
                'approved_by',
                'updated_by',
            ]);
        });
    }
};
