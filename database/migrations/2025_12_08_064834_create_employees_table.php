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
            $table->string('companyCode');
            $table->string('managerId');
            $table->string('location');
            $table->string('country');
            $table->timestamps();
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
