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
        Schema::create('sss_contributions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cutoff_id')
                ->constrained('payroll_cut_offs')
                ->cascadeOnDelete();

            $table->string('empnum');
            $table->string('empname');

            $table->string('sss_number');

            $table->string('year');
            $table->string('month');

            $table->decimal('employee', 10, 2);
            $table->decimal('employer', 10, 2);
            $table->decimal('ec', 10, 2);

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index('cutoff_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sss_contributions');
    }
};
