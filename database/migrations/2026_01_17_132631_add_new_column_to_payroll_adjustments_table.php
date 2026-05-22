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
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            // Decompose computed JSON into columns
            $table->decimal('reg_hours', 5, 2)->after('other_reason')->default(0);
            $table->decimal('overtime', 5, 2)->after('reg_hours')->default(0);
            $table->decimal('late', 5, 2)->after('overtime')->default(0);
            $table->decimal('undertime', 5, 2)->after('late')->default(0);
            $table->decimal('nsd', 5, 2)->after('undertime')->default(0);
            $table->decimal('hours_worked', 5, 2)->after('nsd')->default(0);
            
            // Adjusted columns
            $table->decimal('adjusted_hours', 5, 2)->after('hours_worked')->default(0);
            $table->decimal('adjusted_nsd', 5, 2)->after('adjusted_hours')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->dropColumn([
                'reg_hours', 'overtime', 'late', 'undertime', 'nsd', 'hours_worked',
                'adjusted_hours', 'adjusted_nsd',
            ]);
        });
    }
};
