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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('manager_empnum')->nullable()->after('empnum');
    });

    DB::statement("
        UPDATE employees e
        SET manager_empnum = m.empnum
        FROM employees m
        WHERE e.manager_id = m.id
    ");

    Schema::table('employees', function (Blueprint $table) {
        $table->dropForeign(['manager_id']);
        $table->dropColumn('manager_id');
    });

    Schema::table('employees', function (Blueprint $table) {
        $table->foreign('manager_empnum')
              ->references('empnum')
              ->on('employees')
              ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            //
        });
    }
};
