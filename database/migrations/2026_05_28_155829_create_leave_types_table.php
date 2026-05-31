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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name');

            $table->text('description')->nullable();

            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);

            $table->string('status', 50)->default('Active');

            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE leave_types
            ADD CONSTRAINT leave_types_status_check
            CHECK (status IN ('Active', 'Inactive'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
