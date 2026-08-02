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
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_id')->constrained()->cascadeOnDelete();
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->unsignedSmallInteger('year');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedInteger('file_size');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['measurement_id', 'year', 'quarter']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
