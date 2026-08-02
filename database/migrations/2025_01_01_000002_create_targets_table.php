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
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->decimal('target', 10, 2);
            $table->timestamps();

            $table->unique(['measurement_id', 'year', 'quarter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
