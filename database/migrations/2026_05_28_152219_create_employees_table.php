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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('empnum')->unique();

            $table->string('name');
            $table->string('firstName');
            $table->string('lastName');

            $table->string('nickName')->nullable();
            $table->string('middleName')->nullable();

            $table->string('gender')->nullable();

            $table->string('deptCode');
            $table->string('deptName');

            $table->string('shiftCode');
            $table->string('payGrade');

            $table->decimal('salary', 8, 2);

            $table->string('jobCode');
            $table->string('jobTitle');
            $table->string('businessTitle');

            $table->string('employeeClass');
            $table->string('employeeType');

            $table->string('companyCode');

            $table->foreignId('manager_id')->nullable()->constrained('employees');

            $table->string('location');
            $table->string('country');

            $table->string('sf_person_id')->nullable();
            $table->string('sf_user_id')->nullable();

            $table->integer('standard_hours')->nullable();

            $table->timestamps();

            // Index for fast lookup (kept from MySQL schema)
            $table->index('empnum', 'idx_empnum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
