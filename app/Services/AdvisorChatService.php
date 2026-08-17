<?php

namespace App\Services;

use App\AI\GeminiService;
use App\AI\PromptManager;
use App\Models\AdvisorDocument;
use App\Models\AdvisorMessage;
use App\Models\AuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * AdvisorChatService
 * ------------------
 * Orchestrator alur baru fitur "Strategic Advisor" (knowledge base + Q&A):
 *
 *   1. INGEST — user mengunggah beberapa dokumen PDF (masing-masing maks
 *      50MB). Tiap dokumen diekstrak PER HALAMAN via
 *      `DocumentExtractorService::extractPerPage()` lalu disimpan di tabel
 *      `advisor_documents.pages_json`. TIDAK ada panggilan Gemini di tahap
 *      ini sehingga ingest cepat (hanya pdftotext).
 *
 *   2. ASK — user mengetik pertanyaan / meminta saran. Sistem melakukan
 *      retrieval TF-IDF + cosine similarity terhadap SEMUA halaman SEMUA
 *      dokumen, memilih potongan paling relevan, lalu mengirimnya ke Gemini
 *      (dengan Google Search grounding untuk tren internet terkini).
 *      Gemini wajib mengutip sumber secara presisi: "berdasarkan dokumen X
 *      pada halaman Y". Hasil + sitasi disimpan di `advisor_messages`.
 *
 * Kegagalan Gemini tidak pernah membuang pertanyaan — record tetap
 * tersimpan dengan status=failed + pesan error yang jelas.
 */
class AdvisorChatService
{
    /** Budget total konteks (karakter) yang dikirim ke Gemini per pertanyaan. */
    protected const MAX_CONTEXT_CHARS = 45_000;

    /** Maksimal potongan konteks per dokumen (memaksa keragaman antar dokumen). */
    protected const MAX_CHUNKS_PER_DOCUMENT = 6;

    /** Target & batas ukuran satu potongan konteks (karakter). */
    protected const CHUNK_TARGET_CHARS = 1_800;

    const CHUNK_MAX_CHARS = 2_600;

    public function __construct(
        protected DocumentExtractorService $extractor,
        protected GeminiService $gemini,
        protected PromptManager $promptManager,
    ) {}

    // =========================================================================
    // 1) INGEST DOCUMENT
    // =========================================================================

    /**
     * Simpan file upload dan buat record dokumen berstatus 'processing'.
     * CEPAT (tanpa ekstraksi) — dipanggil endpoint upload; ekstraksi
     * sesungguhnya dilakukan bertahap oleh processDocumentChunk() yang
     * dipolling client, agar dokumen 1000+ halaman tidak menabrak
     * gateway timeout (HTTP 504) di shared hosting.
     */
    public function storeDocument(UploadedFile $file, int $userId): AdvisorDocument
    {
        $originalName = $file->getClientOriginalName();
        $safeName = Str::random(20).'-'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'.pdf';

        $storedPath = $file->storeAs('strategic-advisor', $safeName, 'local');
        if (! $storedPath) {
            throw new \RuntimeException('Gagal menyimpan file upload. Cek permission storage/app/strategic-advisor.');
        }

        return AdvisorDocument::create([
            'user_id' => $userId,
            'name' => $originalName,
            'file_path' => $storedPath,
            'status' => 'processing',
            'pages_json' => [],
        ]);
    }

    /**
     * Proses SATU CHUNK ekstraksi halaman (durasi <= $timeBudget detik).
     * Client memanggil method ini berulang (polling) sampai status
     * 'completed' / 'failed'. Setiap panggilan menambahkan halaman baru
     * ke pages_json sehingga aman dihentikan/konkurensi sederhana.
     */
    public function processDocumentChunk(AdvisorDocument $document, float $timeBudget = 8.0): AdvisorDocument
    {
        if (in_array($document->status, ['completed', 'failed'], true)) {
            return $document;
        }

        @set_time_limit(max(60, (int) ceil($timeBudget * 4)));
        $startedAt = microtime(true);

        try {
            $absPath = Storage::disk('local')->path($document->file_path);
            $existing = $document->pages_json ?? [];

            $chunk = $this->extractor->extractPageChunk(
                $absPath,
                offset: count($existing),
                timeBudget: $timeBudget,
            );

            $pages = array_merge($existing, $chunk['pages']);
            $total = $chunk['total'];
            $finished = $chunk['next_offset'] >= $total;

            $update = [
                'pages_json' => $pages,
                'total_pages' => $total,
                'char_count' => array_sum(array_map('strlen', $pages)),
                'status' => $finished ? 'completed' : 'processing',
            ];

            if ($finished) {
                $fullText = implode("\n", $pages);
                $update = array_merge($update, $this->extractor->analyzeMetadata($fullText));

                if (trim($fullText) === '') {
                    $update['error_message'] = 'Ekstraksi menghasilkan teks kosong — kemungkinan PDF hasil scan (image-only) tanpa layer teks.';
                }

                $elapsed = (int) round(microtime(true) - $startedAt + ($document->processing_time ?: 0));
                $update['processing_time'] = $elapsed;

                AuditTrail::create([
                    'user_id' => $document->user_id,
                    'action' => 'advisor_document_ingested',
                    'model_type' => AdvisorDocument::class,
                    'model_id' => $document->id,
                    'new_values' => [
                        'name' => $document->name,
                        'total_pages' => $total,
                    ],
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]);

                Log::info('AdvisorChat: document ingested', [
                    'document_id' => $document->id,
                    'pages' => $total,
                    'chars' => $update['char_count'],
                ]);
            } else {
                // Akumulasi waktu proses antar-chunk.
                $update['processing_time'] = (int) round(microtime(true) - $startedAt + ($document->processing_time ?: 0));
            }

            $document->update($update);

            return $document->fresh();
        } catch (Throwable $e) {
            Log::error('AdvisorChat: chunk processing failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $document->fresh();
        }
    }

    /**
     * Simpan file, ekstrak teks per halaman, dan persist ke knowledge base.
     * Tidak memanggil Gemini — murni ekstraksi lokal via pdftotext.
     * (Alur sinkron satu-request — HANYA untuk pemanggilan CLI/debug;
     * request HTTP sebaiknya memakai storeDocument + processDocumentChunk.)
     */
    public function ingestDocument(UploadedFile $file, int $userId): AdvisorDocument
    {
        set_time_limit(300);

        $originalName = $file->getClientOriginalName();
        $safeName = Str::random(20).'-'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'.pdf';

        $storedPath = $file->storeAs('strategic-advisor', $safeName, 'local');
        if (! $storedPath) {
            throw new \RuntimeException('Gagal menyimpan file upload. Cek permission storage/app/strategic-advisor.');
        }

        $document = AdvisorDocument::create([
            'user_id' => $userId,
            'name' => $originalName,
            'file_path' => $storedPath,
            'status' => 'processing',
            'pages_json' => [],
        ]);

        $absPath = Storage::disk('local')->path($storedPath);

        try {
            $started = microtime(true);
            $extraction = $this->extractor->extractPerPage($absPath);

            $charCount = array_sum(array_map('strlen', $extraction['pages']));

            $document->update([
                'document_type' => $extraction['document_type'],
                'company' => $extraction['company'],
                'period' => $extraction['period'],
                'total_pages' => $extraction['total_pages'],
                'char_count' => $charCount,
                'pages_json' => $extraction['pages'],
                'status' => 'completed',
                'error_message' => $extraction['error_message'],
                'processing_time' => (int) round(microtime(true) - $started),
            ]);

            AuditTrail::create([
                'user_id' => $userId,
                'action' => 'advisor_document_ingested',
                'model_type' => AdvisorDocument::class,
                'model_id' => $document->id,
                'new_values' => [
                    'name' => $originalName,
                    'document_type' => $extraction['document_type'],
                    'total_pages' => $extraction['total_pages'],
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            Log::info('AdvisorChat: document ingested', [
                'document_id' => $document->id,
                'pages' => $extraction['total_pages'],
                'chars' => $charCount,
            ]);

            return $document->fresh();
        } catch (Throwable $e) {
            Log::error('AdvisorChat: ingest failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $document->fresh();
        }
    }

    /** Hapus dokumen dari knowledge base beserta file fisiknya. */
    public function deleteDocument(AdvisorDocument $document): void
    {
        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }
        $document->delete();
    }

    // =========================================================================
    // 2) ASK (Q&A over knowledge base)
    // =========================================================================

    /**
     * Jawab pertanyaan user berdasarkan seluruh dokumen di knowledge base,
     * lengkap dengan sitasi dokumen+halaman dan tren internet terkini.
     */
    public function ask(string $question, int $userId): AdvisorMessage
    {
        set_time_limit(300);

        $message = AdvisorMessage::create([
            'user_id' => $userId,
            'question' => $question,
            'citations_json' => [],
            'trends_json' => [],
            'recommendations_json' => [],
            'context_documents_json' => [],
            'status' => 'processing',
        ]);

        try {
            // TANPA orderBy: memuat kolom pages_json (bisa MB per baris)
            // sambil ORDER BY akan memicu filesort "Out of sort memory" di
            // MySQL. Tanpa order, InnoDB mengembalikan urutan PK yang sudah
            // kronologis; urutan konteks di-rank ulang oleh retrieval.
            $documents = AdvisorDocument::query()
                ->where('status', 'completed')
                ->get();

            if ($documents->isEmpty()) {
                $message->update([
                    'status' => 'failed',
                    'error_message' => 'Belum ada dokumen di knowledge base. Unggah dokumen terlebih dahulu sebelum bertanya.',
                ]);

                return $message->fresh();
            }

            // 1) Retrieval: pilih potongan halaman paling relevan lintas dokumen.
            $selected = $this->retrieveRelevantChunks($question, $documents);
            $context = $this->buildContextBlocks($selected);
            $docsnMeta = $documents->map(fn (AdvisorDocument $d) => [
                'name' => $d->name,
                'document_type' => $d->document_type,
                'company' => $d->company,
                'period' => $d->period,
                'total_pages' => $d->total_pages,
            ])->all();

            // Snapshot konteks untuk audit trail.
            $contextSnapshot = collect($selected)
                ->map(fn (array $c) => ['document' => $c['document'], 'page' => $c['page']])
                ->unique(fn (array $c) => $c['document'].':'.$c['page'])
                ->values()
                ->all();

            // 2) Riwayat percakapan singkat agar pertanyaan lanjutan dipahami.
            // Kolom besar (raw_response_json) tidak diambil agar hemat memori.
            $history = AdvisorMessage::query()
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->latest()
                ->limit(3)
                ->get(['id', 'question', 'answer', 'created_at'])
                ->reverse()
                ->map(fn (AdvisorMessage $m) => [
                    'question' => Str::limit($m->question, 400),
                    'answer' => Str::limit($m->answer ?? '', 900),
                ])
                ->all();

            // 3) Prompt + Gemini (grounded utk tren internet terkini).
            $prompt = $this->promptManager->generateAdvisorChatPrompt($question, $context, $docsnMeta, $history);
            $started = microtime(true);
            $result = $this->gemini->analyzeWithSearch($prompt);
            $elapsed = round(microtime(true) - $started, 2);

            if (! ($result['success'] ?? false)) {
                $message->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Gemini call failed (unknown reason).',
                    'processing_time' => $result['processing_time'] ?? $elapsed,
                    'raw_response_json' => $result['raw_response'] ?? null,
                    'grounded' => (bool) ($result['grounded'] ?? false),
                ]);

                Log::error('AdvisorChat: Gemini call failed', [
                    'message_id' => $message->id,
                    'error' => $result['error'] ?? null,
                ]);

                return $message->fresh();
            }

            $data = $result['data'] ?? [];
            $grounded = (bool) ($result['grounded'] ?? false);

            $message->update([
                'answer' => $this->takeString($data['answer'] ?? null),
                'citations_json' => $this->normalizeCitations($data['citations'] ?? []),
                'trends_json' => $this->takeArray($data['trends'] ?? []) ?? [],
                'recommendations_json' => $this->takeArray($data['recommendations'] ?? []) ?? [],
                'context_documents_json' => $contextSnapshot,
                'raw_response_json' => $result['raw_response'] ?? null,
                'grounded' => $grounded,
                'status' => 'completed',
                'error_message' => $result['fallback_reason'] ?? null,
                'processing_time' => $result['processing_time'] ?? $elapsed,
            ]);

            AuditTrail::create([
                'user_id' => $userId,
                'action' => 'advisor_question_asked',
                'model_type' => AdvisorMessage::class,
                'model_id' => $message->id,
                'new_values' => [
                    'question' => Str::limit($question, 300),
                    'grounded' => $grounded,
                    'context' => $contextSnapshot,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            Log::info('AdvisorChat: question answered', [
                'message_id' => $message->id,
                'grounded' => $grounded,
                'context_pages' => count($contextSnapshot),
            ]);

            return $message->fresh();
        } catch (Throwable $e) {
            Log::error('AdvisorChat: exception', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 1500),
            ]);

            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $message->fresh();
        }
    }

    // =========================================================================
    // Retrieval — TF-IDF + cosine similarity per halaman lintas dokumen
    // =========================================================================

    /**
     * Bangun potongan (chunk) dari tiap halaman tiap dokumen, rank dengan
     * TF-IDF + cosine similarity terhadap pertanyaan, lalu pilih top chunk
     * dengan batas: maks MAX_CHUNKS_PER_DOCUMENT per dokumen dan total
     * MAX_CONTEXT_CHARS karakter — memaksa jawaban mencakup beberapa
     * dokumen sekaligus bila relevan.
     *
     * @param  iterable<AdvisorDocument>  $documents
     * @return array<int, array{document_id:int, document:string, page:int, text:string, score:float}>
     */
    protected function retrieveRelevantChunks(string $question, iterable $documents): array
    {
        $chunks = [];

        foreach ($documents as $document) {
            foreach ($document->pages_json ?? [] as $i => $pageText) {
                $pageText = trim((string) $pageText);
                if (mb_strlen($pageText) < 60) {
                    continue;
                }

                foreach ($this->splitToChunks($pageText) as $chunk) {
                    $chunks[] = [
                        'document_id' => $document->id,
                        'document' => $document->name,
                        'page' => $i + 1,
                        'text' => $chunk,
                        'tokens' => $this->extractor->tokenize($chunk),
                    ];
                }
            }
        }

        if (empty($chunks)) {
            return [];
        }

        // ---- IDF over chunk corpus ----
        $nDocs = count($chunks);
        $df = [];
        foreach ($chunks as $c) {
            foreach (array_unique($c['tokens']) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }
        $idf = [];
        foreach ($df as $t => $count) {
            $idf[$t] = log((1 + $nDocs) / (1 + $count)) + 1.0;
        }

        // ---- Query vector ----
        $qTf = array_count_values($this->extractor->tokenize($question));
        $qVec = [];
        $qNorm = 0.0;
        foreach ($qTf as $t => $f) {
            $w = $f * ($idf[$t] ?? 0.0);
            $qVec[$t] = $w;
            $qNorm += $w * $w;
        }
        $qNorm = sqrt($qNorm) ?: 1.0;
        foreach ($qVec as $t => $w) {
            $qVec[$t] = $w / $qNorm;
        }

        // ---- Cosine similarity per chunk ----
        foreach ($chunks as &$c) {
            $tf = array_count_values($c['tokens']);
            $norm = 0.0;
            $vec = [];
            foreach ($tf as $t => $f) {
                $w = $f * ($idf[$t] ?? 0.0);
                $vec[$t] = $w;
                $norm += $w * $w;
            }
            $norm = sqrt($norm) ?: 1.0;

            $score = 0.0;
            foreach ($qVec as $t => $qw) {
                if (isset($vec[$t])) {
                    $score += $qw * ($vec[$t] / $norm);
                }
            }
            $c['score'] = $score;
            unset($c['tokens']);
        }
        unset($c);

        // ---- Rank & select dengan diversity constraint ----
        usort($chunks, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $selected = [];
        $perDocument = [];
        $totalChars = 0;

        foreach ($chunks as $c) {
            if ($c['score'] <= 0.0) {
                break;
            }
            $docKey = $c['document_id'];
            if (($perDocument[$docKey] ?? 0) >= self::MAX_CHUNKS_PER_DOCUMENT) {
                continue;
            }

            $selected[] = $c;
            $perDocument[$docKey] = ($perDocument[$docKey] ?? 0) + 1;
            $totalChars += strlen($c['text']) + 64;

            if ($totalChars >= self::MAX_CONTEXT_CHARS) {
                break;
            }
        }

        // Bila pertanyaan tidak match sama sekali (semua score 0), ambil
        // pembuka tiap dokumen sebagai fallback konteks minimal.
        if (empty($selected)) {
            foreach ($documents as $document) {
                $firstPage = trim((string) ($document->pages_json[0] ?? ''));
                if ($firstPage === '') {
                    continue;
                }
                $selected[] = [
                    'document_id' => $document->id,
                    'document' => $document->name,
                    'page' => 1,
                    'text' => Str::limit($firstPage, self::CHUNK_MAX_CHARS),
                    'score' => 0.0,
                ];
            }
        }

        // Urutkan berdasarkan dokumen lalu halaman agar konteks mengalir alami.
        usort($selected, fn (array $a, array $b) => [$a['document'], $a['page']] <=> [$b['document'], $b['page']]);

        return $selected;
    }

    /**
     * Pecah teks satu halaman menjadi potongan berukuran ~CHUNK_TARGET_CHARS
     * pada batas paragraf/baris agar kutipan tidak terpotong di tengah kalimat.
     *
     * @return string[]
     */
    protected function splitToChunks(string $text): array
    {
        if (strlen($text) <= self::CHUNK_MAX_CHARS) {
            return [$text];
        }

        $lines = explode("\n", $text);
        $chunks = [];
        $buffer = '';

        foreach ($lines as $line) {
            if (strlen($buffer) + strlen($line) + 1 > self::CHUNK_TARGET_CHARS && $buffer !== '') {
                $chunks[] = rtrim($buffer);
                $buffer = '';
            }
            $buffer .= $line."\n";
            if (strlen($buffer) >= self::CHUNK_MAX_CHARS) {
                $chunks[] = rtrim($buffer);
                $buffer = '';
            }
        }
        if (rtrim($buffer) !== '') {
            $chunks[] = rtrim($buffer);
        }

        return $chunks;
    }

    /** Susun blok konteks berlabel untuk prompt. */
    protected function buildContextBlocks(array $selected): string
    {
        $blocks = [];
        foreach ($selected as $c) {
            $blocks[] = "===== [DOKUMEN: {$c['document']} | HALAMAN: {$c['page']}] =====\n{$c['text']}";
        }

        return implode("\n\n", $blocks);
    }

    /** Normalisasi daftar sitasi dari response Gemini. */
    protected function normalizeCitations(mixed $citations): array
    {
        if (! is_array($citations) || $citations === []) {
            return [];
        }

        $out = [];
        foreach (array_values($citations) as $c) {
            if (! is_array($c)) {
                continue;
            }
            $out[] = [
                'document' => (string) ($c['document'] ?? ''),
                'page' => (int) ($c['page'] ?? 0),
                'quote' => Str::limit((string) ($c['quote'] ?? ''), 400),
            ];
        }

        return $out;
    }

    protected function takeString(mixed $v): ?string
    {
        return (is_string($v) && trim($v) !== '') ? $v : null;
    }

    protected function takeArray(mixed $v): ?array
    {
        return (is_array($v) && ! empty($v)) ? $v : null;
    }
}
