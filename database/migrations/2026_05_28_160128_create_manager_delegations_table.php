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
        Schema::create('manager_delegations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('manager_id')
                ->constrained('employees');

            $table->foreignId('delegate_id')
                ->constrained('employees');

            $table->date('valid_from');
            $table->date('valid_until');

            $table->timestamps();

            // indexes for fast approval routing
            $table->index('manager_id');
            $table->index('delegate_id');
            $table->index(['manager_id', 'valid_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_delegations');
    }
};
