<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat tanya-jawab Strategic Advisor (alur baru).
     *
     * Satu baris = satu pertanyaan user. Kolom `citations_json` menyimpan
     * daftar sitasi ({document, page, quote}) yang dipakai Gemini saat
     * menjawab, `trends_json` menyimpan tren internet terkini hasil
     * Google Search grounding, dan `context_documents_json` menyimpan
     * snapshot dokumen+halaman mana saja yang dikirim sebagai konteks
     * (untuk audit trail).
     */
    public function up(): void
    {
        Schema::create('advisor_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->longText('question');
            $table->longText('answer')->nullable()->comment('Jawaban utama (markdown, dengan sitasi inline dokumen+halaman)');
            $table->json('citations_json')->comment('[{document,page,quote}]');
            $table->json('trends_json')->comment('[{trend,relevance,source}]');
            $table->json('recommendations_json')->comment('[{title,detail}]');
            $table->json('context_documents_json')->comment('Snapshot dokumen+halaman yang dikirim sebagai konteks');
            $table->json('raw_response_json')->nullable();

            $table->boolean('grounded')->default(false)->comment('Google Search grounding aktif saat menjawab');
            $table->string('status')->default('processing')->comment('processing | completed | failed');
            $table->string('error_message')->nullable();
            $table->decimal('processing_time', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_messages');
    }
};
