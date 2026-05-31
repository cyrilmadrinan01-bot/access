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
        Schema::create('medical_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medical_id')
                ->constrained('medicals')
                ->cascadeOnDelete();

            $table->string('image_path');
            $table->string('original_name');

            $table->timestamps();

            // index for fast retrieval of images per medical record
            $table->index('medical_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_images');
    }
};
