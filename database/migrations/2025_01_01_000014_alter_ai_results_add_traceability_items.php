<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            $table->json('traceability_items')->nullable()->after('investment_items');
        });
    }

    public function down(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            $table->dropColumn('traceability_items');
        });
    }
};