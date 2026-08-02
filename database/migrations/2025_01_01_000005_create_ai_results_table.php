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
        Schema::create('ai_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained()->cascadeOnDelete();
            $table->text('matched_initiative')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->boolean('evidence_valid')->default(false);
            $table->decimal('realisasi', 10, 2)->nullable();
            $table->text('analysis')->nullable();
            $table->text('recommendation')->nullable();
            $table->json('raw_json')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('processing_time', 8, 2)->nullable();
            $table->timestamps();

            $table->index('upload_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_results');
    }
};
