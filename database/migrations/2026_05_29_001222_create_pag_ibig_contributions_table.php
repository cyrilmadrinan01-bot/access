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
        Schema::create('pag_ibig_contributions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cutoff_id');

            $table->string('empnum');
            $table->string('empname');

            $table->string('pagibig_number');

            $table->string('year', 4);
            $table->string('month', 2);

            $table->decimal('employee', 12, 2)
                ->default(0.00);

            $table->decimal('employer', 12, 2)
                ->default(0.00);

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

             $table->index(
                ['cutoff_id', 'year', 'month'],
                'pag_ibig_contributions_cutoff_id_year_month_index'
            );

             $table->foreign('cutoff_id')
                 ->references('id')
                 ->on('payroll_cut_offs')
                 ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pag_ibig_contributions');
    }
};
