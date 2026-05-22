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
        Schema::create('bank_files', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('cutoff_id');
            $table->string('empnum');
            $table->string('employee_name');
            $table->string('account_number');
            $table->decimal('amount', 12, 2);
            $table->string('reference_number')->nullable();

            $table->timestamps();

            $table->unique(['cutoff_id', 'empnum']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_files');
    }
};
