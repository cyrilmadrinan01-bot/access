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
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('original_correction_id')->constrained('timekeeping_corrections')->cascadeOnDelete();
    $table->foreignId('timekeeping_id')->constrained('timekeepings')->cascadeOnDelete();
    $table->foreignId('payroll_cut_off_id')->constrained('payroll_cut_offs')->cascadeOnDelete();

    $table->string('empnum');
    $table->date('dated');

    $table->foreignId('shiftcode_id')->constrained('shiftcodes')->cascadeOnDelete();
    $table->dateTime('time_in');
    $table->dateTime('time_out');

    $table->foreignId('reason_id')->constrained('reasons')->cascadeOnDelete();

    $table->string('other_reason')->nullable();

    $table->decimal('reg_hours', 5, 2)->default(0);
    $table->decimal('overtime', 5, 2)->default(0);
    $table->decimal('late', 5, 2)->default(0);
    $table->decimal('undertime', 5, 2)->default(0);
    $table->decimal('nsd', 5, 2)->default(0);
    $table->decimal('hours_worked', 5, 2)->default(0);
    $table->decimal('adjusted_hours', 5, 2)->default(0);
    $table->decimal('adjusted_nsd', 5, 2)->default(0);

    $table->foreignId('approved_by')->nullable()->constrained('users');

    $table->timestamp('approved_at')->nullable();
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
