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
        Schema::create('payroll_process_statuses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cutoff_id');

            $table->string('step');

            $table->string('status')
                ->default('pending');

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            // Optional foreign key
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
        Schema::dropIfExists('payroll_process_statuses');
    }
};
