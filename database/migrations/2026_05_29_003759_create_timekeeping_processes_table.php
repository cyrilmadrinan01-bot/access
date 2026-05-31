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

            $table->foreignId('payroll_cut_offs_id')
                ->constrained('payroll_cut_offs')
                ->cascadeOnDelete();

            $table->string('empnum');

            $table->decimal('reg', 5, 2)->default(0.00);
            $table->decimal('nsd_reg', 5, 2)->default(0.00);

            $table->decimal('overtime_reg', 5, 2)->default(0.00);
            $table->decimal('overtime_lh', 5, 2)->default(0.00);
            $table->decimal('overtime_sh', 5, 2)->default(0.00);
            $table->decimal('overtime_lhrd', 5, 2)->default(0.00);
            $table->decimal('overtime_shrd', 5, 2)->default(0.00);
            $table->decimal('overtime_rd', 5, 2)->default(0.00);

            $table->decimal('overtime_nsd_lh', 5, 2)->default(0.00);
            $table->decimal('overtime_nsd_sh', 5, 2)->default(0.00);
            $table->decimal('overtime_nsd_lhrd', 5, 2)->default(0.00);
            $table->decimal('overtime_nsd_shrd', 5, 2)->default(0.00);
            $table->decimal('overtime_nsd_rd', 5, 2)->default(0.00);
            $table->decimal('overtime_nsd_reg', 5, 2)->nullable();

            $table->decimal('late_reg', 5, 2)->default(0.00);
            $table->decimal('undertime', 5, 2)->default(0.00);
            $table->decimal('absent', 5, 2)->nullable();

            $table->decimal('adjusted_hours', 5, 2)->nullable();
            $table->decimal('adjusted_nsd', 5, 2)->nullable();
            $table->decimal('adjusted_ot_hours', 5, 2)->nullable();
            $table->decimal('adjusted_ot_nsd', 5, 2)->nullable();

            $table->decimal('overtime_lh_8', 5, 2)->nullable();
            $table->decimal('overtime_lh_12', 5, 2)->nullable();
            $table->decimal('overtime_sh_8', 5, 2)->nullable();
            $table->decimal('overtime_sh_12', 5, 2)->nullable();
            $table->decimal('overtime_lhrd_8', 5, 2)->nullable();
            $table->decimal('overtime_lhrd_12', 5, 2)->nullable();
            $table->decimal('overtime_shrd_8', 5, 2)->nullable();
            $table->decimal('overtime_shrd_12', 5, 2)->nullable();
            $table->decimal('overtime_rd_8', 5, 2)->nullable();
            $table->decimal('overtime_rd_12', 5, 2)->nullable();

            $table->decimal('meal', 5, 2)->nullable();
            $table->decimal('adjusted_meal', 5, 2)->nullable();

            $table->decimal('gross', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(
                ['payroll_cut_offs_id', 'empnum'],
                'timekeeping_processes_payroll_cut_offs_id_empnum_unique'
            );
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
