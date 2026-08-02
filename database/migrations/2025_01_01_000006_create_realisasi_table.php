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
        Schema::create('realisasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_id')->constrained()->cascadeOnDelete();
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->unsignedSmallInteger('year');
            $table->decimal('value', 10, 2);
            $table->enum('source', ['ai', 'manual'])->default('ai');
            $table->timestamps();

            $table->unique(['measurement_id', 'year', 'quarter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realisasi');
    }
};
