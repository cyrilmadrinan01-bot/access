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
        Schema::create('timekeeping_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timekeeping_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shiftcode_id')->constrained('shiftcodes');
            $table->foreignId('reason_id')->constrained('reasons');
            $table->datetime('time_in');
            $table->datetime('time_out');
            $table->string('other_reason')->nullable();
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timekeeping_corrections');
    }
};
