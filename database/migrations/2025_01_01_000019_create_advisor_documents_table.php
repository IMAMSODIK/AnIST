<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Knowledge base untuk fitur "Strategic Advisor" (alur baru).
     *
     * Alur: user mengunggah beberapa dokumen (masing-masing maks 50MB),
     * sistem mengekstrak teks PER HALAMAN (pdftotext, dipisah form-feed)
     * dan menyimpannya di `pages_json`. Teks per halaman inilah yang
     * dipakai sebagai konteks saat user bertanya / meminta saran —
     * sehingga Gemini dapat mengutip "dokumen X halaman Y" secara akurat.
     */
    public function up(): void
    {
        Schema::create('advisor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name')->comment('Original uploaded filename (display name)');
            $table->string('file_path')->comment('Relative path under storage/app/strategic-advisor');

            $table->string('document_type')->default('unknown')->comment('RJPP | MPTI | unknown');
            $table->string('company')->nullable();
            $table->string('period')->nullable();
            $table->unsignedInteger('total_pages')->default(0);
            $table->unsignedBigInteger('char_count')->default(0);

            $table->json('pages_json')->comment('Array of per-page extracted text (1-based index = page number)');

            $table->string('status')->default('processing')->comment('processing | completed | failed');
            $table->string('error_message')->nullable();
            $table->unsignedInteger('processing_time')->default(0)->comment('Detik ekstraksi');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_documents');
    }
};
