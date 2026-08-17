<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sesi chat Strategic Advisor (gaya ChatGPT): satu sesi = satu
     * percakapan milik satu user. advisor_messages mendapat kolom
     * session_id (nullable agar pesan lama tetap valid).
     */
    public function up(): void
    {
        Schema::create('advisor_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Percakapan baru');
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_activity_at']);
        });

        Schema::table('advisor_messages', function (Blueprint $table) {
            $table->foreignId('advisor_session_id')
                ->nullable()
                ->after('user_id')
                ->constrained('advisor_sessions')
                ->nullOnDelete();
            $table->index('advisor_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('advisor_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advisor_session_id');
        });

        Schema::dropIfExists('advisor_sessions');
    }
};
