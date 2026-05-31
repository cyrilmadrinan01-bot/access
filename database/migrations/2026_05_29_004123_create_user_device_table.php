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
        Schema::create('user_device', function (Blueprint $table) {
            $table->id();

            $table->string('user_empnum', 50);

            $table->foreignId('device_id')
                ->constrained('device')
                ->cascadeOnDelete();

            $table->string('deviceIp', 50)->nullable();

            $table->timestamps();

            $table->unique(['user_empnum', 'device_id'], 'user_device_user_empnum_device_id_unique');

            $table->index('device_id');
            $table->index('user_empnum');
            $table->index('deviceIp');
            $table->index(['user_empnum', 'deviceIp']);

            $table->foreign('user_empnum')
                ->references('empnum')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_device');
    }
};
