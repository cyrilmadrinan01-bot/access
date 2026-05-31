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

            $table->foreignId('timekeeping_id')
                ->constrained('timekeepings')
                ->cascadeOnDelete();

            $table->foreignId('payroll_cut_off_id')
                ->nullable()
                ->constrained('payroll_cut_offs')
                ->cascadeOnDelete();

            $table->foreignId('shiftcode_id')
                ->constrained('shiftcodes');

            $table->foreignId('reason_id')
                ->constrained('reasons');

            $table->dateTime('time_in');
            $table->dateTime('time_out');

            $table->string('other_reason')->nullable();

            $table->string('status')->default('Pending');

            $table->boolean('is_adjustment')->default(false);

            $table->foreignId('created_by')
                ->constrained('users');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users');

            $table->timestamp('approved_at')->nullable();

            $table->string('rejected_reason')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // indexes (Laravel auto-indexes foreignId, but keeping explicit clarity optional)
            $table->index('timekeeping_id');
            $table->index('shiftcode_id');
            $table->index('reason_id');
            $table->index('created_by');
            $table->index('approved_by');
            $table->index('payroll_cut_off_id');
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
