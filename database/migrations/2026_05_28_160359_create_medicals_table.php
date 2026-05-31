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
        Schema::create('medicals', function (Blueprint $table) {
            $table->id();

            $table->string('reqid');

            $table->string('empnum');
            $table->string('empname');

            $table->string('receiptNumber');

            $table->decimal('amount', 10, 3);

            $table->date('transactionDate');

            $table->text('employeeNotes')->nullable();
            $table->date('payout')->nullable();

            $table->text('adminNotes')->nullable();

            $table->string('status');

            $table->timestamp('approved_at')->nullable();

            $table->string('processed')->nullable();

            $table->timestamps();

             // indexes for performance
            $table->index('empnum');
            $table->index('reqid');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicals');
    }
};
