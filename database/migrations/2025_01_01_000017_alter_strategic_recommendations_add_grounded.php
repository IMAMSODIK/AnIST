<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a `grounded` flag column to strategic_recommendations. Set to TRUE
     * when Gemini successfully used google_search_retrieval (live web grounding),
     * FALSE when the call fell back to non-grounded mode (free-tier API keys
     * frequently reject the grounding tool with HTTP 429 / limit: 0 — this
     * fallback lets the feature still function using the model's training
     * knowledge, producing strategic analysis without live internet trends).
     */
    public function up(): void
    {
        Schema::table('strategic_recommendations', function (Blueprint $table) {
            $table->boolean('grounded')->default(false)->after('processing_time');
        });
    }

    public function down(): void
    {
        Schema::table('strategic_recommendations', function (Blueprint $table) {
            $table->dropColumn('grounded');
        });
    }
};