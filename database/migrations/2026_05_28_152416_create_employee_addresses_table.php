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
        Schema::create('employee_addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees');

            $table->string('type');

            $table->string('address_line1');
            $table->string('address_line2')->nullable();

            $table->string('city');
            $table->string('province')->nullable();

            $table->string('postal_code')->nullable();

            $table->string('country')->default('PH');

            $table->date('effective_start');
            $table->date('effective_end')->nullable();

            $table->timestamps();

            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_addresses');
    }
};
