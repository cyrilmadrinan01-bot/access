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
        Schema::create('payroll_medicals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cutoff_id')
                ->constrained('payroll_cut_offs')
                ->cascadeOnDelete();

            $table->string('empnum');
            $table->string('empname');

            $table->decimal('total_amount', 12, 2);

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['cutoff_id', 'empnum'],
                'payroll_medicals_cutoff_id_empnum_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_medicals');
    }
};
