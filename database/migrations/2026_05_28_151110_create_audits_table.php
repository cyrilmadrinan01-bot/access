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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();

             $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('event');

            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');

            // In PostgreSQL, TEXT is fine for JSON-like storage unless you prefer jsonb
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();

            $table->text('url')->nullable();

            $table->string('ip_address', 45)->nullable();

            // 1023 is fine but PostgreSQL has no strict varchar limit enforcement
            $table->string('user_agent', 1023)->nullable();

            $table->string('tags')->nullable();

            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id'], 'audits_auditable_type_auditable_id_index');
            $table->index(['user_id', 'user_type'], 'audits_user_id_user_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
