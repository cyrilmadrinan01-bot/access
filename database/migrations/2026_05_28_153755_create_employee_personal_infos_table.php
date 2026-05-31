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
        Schema::create('employee_personal_infos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->string('first_name');
            $table->string('last_name');

            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();

            $table->date('effective_start');
            $table->date('effective_end')->nullable();

            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();

            $table->string('marital_status')->nullable();
            $table->string('salutation')->nullable();
            $table->string('prefix')->nullable();

            $table->string('nationality')->nullable();
            $table->string('religion')->nullable();
            $table->string('country_of_birth')->nullable();

            $table->timestamps();

            // Index for fast employee lookup
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_personal_infos');
    }
};
