<?php

namespace App\Services;

use App\AI\GeminiService;
use App\AI\PromptManager;
use App\AI\ResponseValidator;
use App\DTO\AiResultDTO;
use App\Models\AiResult;
use App\Models\AuditTrail;
use App\Models\Measurement;
use App\Models\Realisasi;
use App\Models\Upload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EvidenceService
{
    public function __construct(
        protected GeminiService $gemini,
        protected PromptManager $promptManager,
        protected ResponseValidator $validator,
        protected KPIService $kpiService,
        protected InsightService $insightService,
    ) {}

    public function processEvidence(Upload $upload): AiResult
    {
        $upload->update(['status' => 'processing']);
        $this->logAudit('request_ai', $upload, ['status' => 'processing']);

        try {
            $measurement = $upload->measurement;
            $initiatives = $measurement->initiatives->pluck('initiative')->toArray();

            $prompt = $this->promptManager->generatePrompt($measurement, $initiatives);

            $fileContent = Storage::disk('local')->get($upload->file_path);
            $fileBase64 = base64_encode($fileContent);
            $mimeType = $upload->file_type;

            $response = $this->gemini->analyzeEvidence($prompt, $fileBase64, $mimeType);

            $this->logAudit('response_ai', $upload, [
                'success' => $response['success'],
                'processing_time' => $response['processing_time'] ?? null,
            ]);

            if (!$response['success']) {
                $dto = AiResultDTO::fromError(
                    error: $response['error'] ?? 'Unknown AI error',
                    processingTime: $response['processing_time'] ?? 0,
                    rawResponse: $response['raw_response'] ?? null,
                );
                return $this->storeFailedResult($upload, $dto);
            }

            $validation = $this->validator->validate($response['data']);

            $this->logAudit('validate_result', $upload, [
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
            ]);

            if (!$validation['valid']) {
                $dto = AiResultDTO::fromError(
                    error: 'Validation failed: ' . implode(', ', $validation['errors']),
                    processingTime: $response['processing_time'] ?? 0,
                    rawResponse: $response['raw_response'] ?? null,
                );
                return $this->storeFailedResult($upload, $dto);
            }

            $dto = AiResultDTO::fromAiResponse($validation['data'], $response);
            $aiResult = $this->storeAiResult($upload, $dto);

            if ($dto->evidenceValid) {
                $this->updateRealisasi($upload, $dto->realisasi);

                $this->kpiService->calculateScore(
                    $upload->measurement_id,
                    $upload->quarter,
                    $upload->year
                );

                $this->logAudit('calculate_kpi', $upload, [
                    'measurement_id' => $upload->measurement_id,
                    'quarter' => $upload->quarter,
                    'year' => $upload->year,
                ]);

                // Refresh the narrative insight so the reports page always
                // reflects the latest evidence. Wrapped in try-catch so an
                // AI hiccup here can never break the upload flow.
                try {
                    $this->insightService->generateInsight(
                        $upload->measurement_id,
                        $upload->quarter,
                        $upload->year
                    );
                } catch (\Throwable $e) {
                    Log::warning('Insight refresh after evidence processing failed (suppressed)', [
                        'upload_id' => $upload->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $upload->update(['status' => 'completed']);
            return $aiResult;

        } catch (\Exception $e) {
            Log::error('Evidence processing failed', [
                'upload_id' => $upload->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $upload->update(['status' => 'failed']);

            $dto = AiResultDTO::fromError(error: $e->getMessage());

            return AiResult::create([
                'upload_id' => $upload->id,
                ...$dto->toArray(),
            ]);
        }
    }

    /**
     * Persist a failed/error AI result derived from a DTO.
     */
    protected function storeFailedResult(Upload $upload, AiResultDTO $dto): AiResult
    {
        $upload->update(['status' => 'failed']);

        return AiResult::create([
            'upload_id' => $upload->id,
            ...$dto->toArray(),
        ]);
    }

    /**
     * Persist a successful AI result derived from a DTO.
     */
    protected function storeAiResult(Upload $upload, AiResultDTO $dto): AiResult
    {
        return AiResult::create([
            'upload_id' => $upload->id,
            ...$dto->toArray(),
        ]);
    }

    protected function updateRealisasi(Upload $upload, float $value): Realisasi
    {
        return $this->recalculateRealisasi(
            $upload->measurement_id,
            $upload->quarter,
            $upload->year
        );
    }

    /**
     * Recompute and persist the aggregated realisasi for a measurement +
     * period from all currently-valid ai_results. Safe to call after a new
     * evidence is processed OR after an evidence is deleted.
     *
     * For Implementasi Sistem measurements, realisasi = count of UNIQUE
     * application names across all valid evidence (so two evidence files for
     * the same application count as one). For every other measurement, it is
     * the SUM of each evidence's realisasi.
     */
    public function recalculateRealisasi(int $measurementId, string $quarter, int $year): Realisasi
    {
        $measurement = Measurement::find($measurementId);

        // For Implementasi Sistem measurements, each evidence documents one or
        // more applications at various stages (UAT, go-live, etc.). Two
        // evidence files for the SAME application must count as ONE, not two.
        // We therefore count the number of UNIQUE application names across all
        // valid ai_results for the period, instead of SUM(realisasi).
        if ($this->isImplementasiSistem($measurement)) {
            $totalRealisasi = $this->countUniqueApplications($measurementId, $quarter, $year);
        } else {
            // Default: SUM realisasi dari semua AiResult yang valid untuk
            // measurement+quarter+year sehingga setiap upload evidence baru
            // menambah total, bukan menimpa.
            $totalRealisasi = AiResult::whereHas('upload', function ($q) use ($measurementId, $quarter, $year) {
                $q->where('measurement_id', $measurementId)
                    ->where('quarter', $quarter)
                    ->where('year', $year);
            })
                ->where('evidence_valid', true)
                ->sum('realisasi');
        }

        return Realisasi::updateOrCreate(
            [
                'measurement_id' => $measurementId,
                'quarter' => $quarter,
                'year' => $year,
            ],
            [
                'value' => $totalRealisasi,
                'source' => 'ai',
            ]
        );
    }

    /**
     * Determine whether the measurement is an "Implementasi Sistem" type, which
     * counts the number of implemented systems/applications.
     */
    protected function isImplementasiSistem($measurement): bool
    {
        if (!$measurement) {
            return false;
        }

        $name = strtolower($measurement->measurement ?? '');

        return str_contains($name, 'implementasi sistem')
            || str_contains($name, 'system implementation');
    }

    /**
     * Refresh the narrative insight for a measurement + period without
     * throwing. Exposed publicly so callers (e.g. UploadService after a
     * delete) can keep the insight in sync with the latest realisasi.
     */
    public function refreshInsight(int $measurementId, string $quarter, int $year): void
    {
        try {
            $this->insightService->generateInsight($measurementId, $quarter, $year);
        } catch (\Throwable $e) {
            Log::warning('Insight refresh failed (suppressed)', [
                'measurement_id' => $measurementId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Count UNIQUE application names across all valid ai_results for the given
     * measurement + period, considering ONLY applications whose stage in
     * their evidence is Go Live / production deployment. UAT-only evidence
     * does not contribute to realisasi (an application that has only reached
     * UAT is not yet "completed").
     *
     * Matching is done on a SET of distinctive tokens (lowercased,
     * punctuation-stripped, with generic wrapper words removed) so that the
     * AI can return the same application using slightly different phrasings
     * on different evidence files — e.g.
     *   "ESS - Klaim Kacamata"   (UAT evidence)
     *   "Sistem Layanan Reimburse Kesehatan (Modul Klaim Kacamata)"  (Go Live)
     * — and the system still collapses them to a single application.
     *
     * Two application names are treated as the SAME application when:
     *   - They share the exact same set of distinctive tokens, OR
     *   - One distinctive-token set is a subset of the other AND the smaller
     *     set contains at least 2 tokens, OR
     *   - Jaccard similarity ≥ 0.75 (minor wording variance tie-breaker).
     */
    protected function countUniqueApplications(int $measurementId, string $quarter, int $year): float
    {
        $aiResults = AiResult::whereHas('upload', function ($q) use ($measurementId, $quarter, $year) {
            $q->where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year);
        })
            ->where('evidence_valid', true)
            ->get();

        // Each cluster is keyed by sorted distinctive-token set. A cluster
        // counts toward realisasi only when at least one of its members was
        // contributed from a Go Live evidence (via go_live_applications).
        $clusters = [];

        foreach ($aiResults as $result) {
            // Process Go Live applications only — UAT-only evidence does NOT
            // count toward realisasi. Legacy rows (NULL column) are handled
            // by the model accessor which infers the stage from analysis.
            $goLiveApps = $result->go_live_applications_array ?? [];

            foreach ($goLiveApps as $app) {
                $tokens = $this->extractApplicationTokens($app);

                if (empty($tokens['set'])) {
                    continue;
                }

                $matchedKey = null;
                foreach ($clusters as $key => $clusterTokens) {
                    if ($this->applicationTokensMatch($tokens, $clusterTokens)) {
                        $matchedKey = $key;
                        break;
                    }
                }

                if ($matchedKey === null) {
                    $clusters[$tokens['key']] = $tokens;
                }
            }
        }

        return (float) count($clusters);
    }

    /**
     * Extract a normalized set of DISTINCTIVE tokens from an application name.
     *
     * This is the heart of the deduplication logic: punctuation and common
     * wrapper/stage words are stripped, then the remaining distinctive words
     * (e.g. "klaim" + "kacamata") are returned as both an associative set
     * (for set comparison) and a sorted "key" (for fast exact-lookups).
     *
     * Returned array shape:
     *   [
     *     'set'    => ['klaim' => true, 'kacamata' => true],
     *     'sorted' => ['kacamata', 'klaim'],
     *     'key'    => 'kacamata klaim',
     *   ]
     */
    protected function extractApplicationTokens(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['set' => [], 'sorted' => [], 'key' => ''];
        }

        $name = strtolower($name);

        // Remove multi-word stage / document phrases BEFORE tokenizing so the
        // phrase match works even when written with spaces (e.g. "Go Live",
        // "Berita Acara").
        $stageNoise = [
            'berita acara',
            'go live',
            'go-live',
            'golive',
            'user acceptance test',
            'user acceptance testing',
            'test script',
        ];

        foreach ($stageNoise as $word) {
            $name = str_replace($word, ' ', $name);
        }

        // Strip punctuation / separators, then split into tokens.
        $name = preg_replace('/[\(\)\[\]\{\}\-\/\\\:,\.\;\!"\'\?&\|+]/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        $tokens = array_values(array_filter(explode(' ', $name), fn ($t) => $t !== ''));

        // Single-word / multi-word noise that wraps canonical names without
        // distinguishing one application from another.
        $genericNoise = [
            // prefixes / wrappers
            'sistem', 'system', 'sistem', 'aplikasi', 'application', 'app',
            'modul', 'module', 'layanan', 'service', 'platform',
            'fitur', 'feature', 'fungsi', 'function', 'solusi', 'solution',
            'produk', 'product',
            // channel / doc / stage tokens
            'ess', 'hris', 'erp', 'crm', 'portal', 'web', 'frontend', 'backend',
            'self', 'service',
            'dokumen', 'document', 'evidence',
            'uat', 'testing', 'test', 'script',
            // versioning / wrapping
            'baru', 'new', 'upgrade', 'upgrades', 'version', 'versi',
            'reimburse', 'reimbursement', 'refund', 'kesehatan', 'health',
            'implementasi', 'implementation',
        ];

        $set = [];
        foreach ($tokens as $t) {
            if (in_array($t, $genericNoise, true)) {
                continue;
            }
            if (mb_strlen($t) < 2) {
                continue;
            }
            $set[$t] = true;
        }

        $sorted = array_keys($set);
        sort($sorted);

        return [
            'set'    => $set,
            'sorted' => $sorted,
            'key'    => implode(' ', $sorted),
        ];
    }

    /**
     * Decide whether two application-token extracts refer to the SAME
     * application. Three rules (any one wins):
     *
     *   1. Exact same distinctive-token set ("Aplikasi Mobile Banking" vs
     *      "Mobile Banking Platform").
     *   2. Subset rule: one set ⊆ the other and the smaller has ≥2 tokens —
     *      absorbs "Klaim Kacamata" inside "Sistem Layanan Reimburse
     *      Kesehatan (Modul Klaim Kacamata)".
     *   3. Jaccard similarity ≥ 0.75 — minor wording variance fallback.
     */
    protected function applicationTokensMatch(array $a, array $b): bool
    {
        $setA = $a['set'];
        $setB = $b['set'];

        if (empty($setA) || empty($setB)) {
            return false;
        }

        // 1. Exact-equality fast path.
        if ($setA === $setB) {
            return true;
        }

        $countA = count($setA);
        $countB = count($setB);

        [$small, $big] = $countA <= $countB
            ? [$setA, $setB]
            : [$setB, $setA];

        // 2. Subset rule (canonical short name wrapped by longer name).
        if (count($small) >= 2) {
            $intersection = count(array_intersect_key($small, $big));

            if ($intersection === count($small)) {
                return true;
            }
        }

        // 3. Jaccard similarity tie-breaker.
        $intersection = count(array_intersect_key($setA, $setB));
        $union = $countA + $countB - $intersection;

        return $union > 0 && ($intersection / $union) >= 0.75;
    }

    /**
     * Backwards-compatible string normalizer kept for any external callers
     * that relied on the legacy behavior. Internally, deduplication now uses
     * {@see extractApplicationTokens} + {@see applicationTokensMatch} which
     * handles the same-application-with-different-wording cases this method
     * could not.
     */
    protected function normalizeApplicationName(string $name): string
    {
        $tokens = $this->extractApplicationTokens($name);

        return $tokens['key'];
    }

    protected function logAudit(string $action, Upload $upload, array $newValues = []): void
    {
        try {
            AuditTrail::create([
                'user_id' => $upload->uploaded_by,
                'action' => $action,
                'model_type' => Upload::class,
                'model_id' => $upload->id,
                'new_values' => $newValues,
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ?? null,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log audit trail', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
