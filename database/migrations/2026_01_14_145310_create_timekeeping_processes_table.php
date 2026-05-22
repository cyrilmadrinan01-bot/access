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
        Schema::create('timekeeping_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_cut_offs_id')->constrained()->cascadeOnDelete();
            $table->string('empnum');
            
            $table->decimal('regular_hours', 5, 2)->default(0);
            $table->decimal('nsd', 5, 2)->default(0);

            $table->decimal('overtime', 5, 2)->default(0);
            $table->decimal('overtime_lh', 5, 2)->default(0);
            $table->decimal('overtime_sh', 5, 2)->default(0);
            $table->decimal('overtime_lhrd', 5, 2)->default(0);
            $table->decimal('overtime_shrd', 5, 2)->default(0);
            $table->decimal('overtime_rd', 5, 2)->default(0);

            $table->decimal('overtime_nsd_lh', 5, 2)->default(0);
            $table->decimal('overtime_nsd_sh', 5, 2)->default(0);
            $table->decimal('overtime_nsd_lhrd', 5, 2)->default(0);
            $table->decimal('overtime_nsd_shrd', 5, 2)->default(0);
            $table->decimal('overtime_nsd_rd', 5, 2)->default(0);

            $table->decimal('late', 5, 2)->default(0);
            $table->decimal('undertime', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_cut_offs_id', 'empnum']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timekeeping_processes');
    }
};
