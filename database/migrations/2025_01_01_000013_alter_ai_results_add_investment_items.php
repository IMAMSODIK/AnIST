<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            $table->json('investment_items')->nullable()->after('sla_targets');
        });
    }

    public function down(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            $table->dropColumn('investment_items');
        });
    }
};