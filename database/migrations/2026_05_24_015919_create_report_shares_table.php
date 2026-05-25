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
        Schema::create('report_shares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('report_id')->constrained()->cascadeOnDelete();

            $table->foreignId('shared_to_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('shared_to_role')->nullable();

            $table->enum('permission', [
                'view',
                'edit',
                'manage'
            ])->default('view');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_shares');
    }
};
