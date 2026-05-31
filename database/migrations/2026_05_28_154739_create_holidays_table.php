<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->date('date');

            // PostgreSQL-safe enum replacement
            $table->string('type');

            $table->string('day_type_code');

            $table->timestamps();

            $table->index('date');
        });

        DB::statement("
            ALTER TABLE holidays
            ADD CONSTRAINT holidays_type_check
            CHECK (type IN ('Legal', 'Special'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
