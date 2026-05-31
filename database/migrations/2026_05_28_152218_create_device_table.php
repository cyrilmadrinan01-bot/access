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
        Schema::create('device', function (Blueprint $table) {
            $table->id();

            $table->string('deviceName');
            $table->string('deviceType');
            $table->string('deviceIp');
            $table->string('location');

            // PostgreSQL-safe enum replacement
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index('deviceIp');
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE device
            ADD CONSTRAINT device_status_check
            CHECK (status IN ('active', 'inactive'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device');
    }
};
