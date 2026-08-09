<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the result of the "Strategic Advisor" feature: a user uploads a
     * strategic reference PDF (RJPP / MPTI / external research), the system
     * extracts its structure via DocumentExtractorService, then asks Gemini —
     * with Google Search grounding enabled — to:
     *   1. analyze the document,
     *   2. recommend strategic actions,
     *   3. surface current internet trends relevant to the document's domain.
     * The full extraction summary and raw AI response are persisted for audit.
     */
    public function up(): void
    {
        Schema::create('strategic_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('source_file')->comment('Original uploaded filename');
            $table->string('file_path')->comment('Relative path under storage/app/strategic-advisor');

            $table->string('document_type')->default('unknown')->comment('RJPP | MPTI | unknown');
            $table->string('company')->nullable();
            $table->string('period')->nullable();
            $table->unsignedInteger('total_pages')->default(0);

            $table->json('extraction_json')->nullable()->comment('Compact DocumentExtractionDTO');
            $table->json('matched_kpis_json')->nullable();
            $table->json('matched_initiatives_json')->nullable();
            $table->json('recommendations_json')->nullable();
            $table->json('popular_trends_json')->nullable();
            $table->json('perspective_coverage_json')->nullable();
            $table->longText('analysis')->nullable();

            $table->json('raw_response_json')->nullable();

            $table->string('status')->default('pending')->comment('pending | processing | completed | failed');
            $table->string('error_message')->nullable();
            $table->decimal('processing_time', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_recommendations');
    }
};