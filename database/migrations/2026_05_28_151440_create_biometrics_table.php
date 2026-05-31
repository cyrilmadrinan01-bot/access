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
        Schema::create('biometrics', function (Blueprint $table) {
            $table->id();

            $table->string('empnum');

            $table->timestamp('timeLog');
            $table->string('deviceIp');
            $table->string('dayName');
            $table->date('dated');

            $table->string('processed');

            // PostgreSQL-safe replacement for ENUM
            $table->string('logType');

            $table->integer('retry_count')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamps();

            // Indexes (recommended for biometric logs)
            $table->index('empnum');
            $table->index('dated');
            $table->index('timeLog');
        });

         DB::statement("
            ALTER TABLE biometrics
            ADD CONSTRAINT biometrics_logtype_check
            CHECK (\"logType\" IN ('Clock In', 'Clock Out'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometrics');
    }
};
