<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            $table->json('sla_targets')->nullable()->after('go_live_applications');
        });
    }

    public function down(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            $table->dropColumn('sla_targets');
        });
    }
};
