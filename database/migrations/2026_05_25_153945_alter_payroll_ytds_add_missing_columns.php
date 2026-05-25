<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_ytds', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Benefits
            |--------------------------------------------------------------------------
            */
            $table->decimal('non_taxable_benefits', 15, 2)->default(0);

            $table->decimal('taxable_benefits', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('payroll_ytds', function (Blueprint $table) {

            $table->dropColumn([
                'non_taxable_benefits',
                'taxable_benefits',
            ]);
        });
    }
};