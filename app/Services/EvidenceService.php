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
        if ($this->isRstiMeasurement($measurement)) {
            $totalRealisasi = $this->calculateRstiRealisasi($measurementId, $quarter, $year);
        } elseif ($this->isIsoCertification($measurement)) {
            // Certification-progress percentage: MAX across evidence —
            // multiple documents in one period each prove a progress stage,
            // and the most advanced evidence defines the period's progress.
            $totalRealisasi = AiResult::whereHas('upload', function ($q) use ($measurementId, $quarter, $year) {
                $q->where('measurement_id', $measurementId)
                    ->where('quarter', $quarter)
                    ->where('year', $year);
            })
                ->where('evidence_valid', true)
                ->max('realisasi') ?? 0;
        } elseif ($this->isImplementasiSistem($measurement)) {
            $totalRealisasi = $this->countUniqueApplications($measurementId, $quarter, $year);
        } elseif ($this->isInvestmentRealisasi($measurement)) {
            $totalRealisasi = $this->calculateInvestmentRealisasi($measurementId, $quarter, $year);
        } elseif ($this->isProjectManagementTraceability($measurement)) {
            $totalRealisasi = $this->calculateTraceabilityCoverage($measurementId, $quarter, $year);
        } elseif ($this->isSlaAvailabilityMeasurement($measurement)) {
            $totalRealisasi = $this->calculateSlaAvailabilityRealisasi($measurementId, $quarter, $year);
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
            || str_contains($name, 'system implementation')
            // "Jumlah proses supporting unit yang menggunakan AI" (OMTI 2026
            // #8) reuses the same count-of-unique-go-live-apps aggregation
            // (each "application" is a supporting-unit process that has
            // adopted AI). Keep this check in sync with the matching
            // detection in PromptManager::getOutputFormat() so DB
            // aggregation and the prompt format stay aligned.
            || str_contains($name, 'supporting unit');
    }

    /**
     * Determine whether the measurement is a "Pemenuhan Sertifikasi
     * Internasional ISO 27001" type, whose realisasi is a certification
     * fulfillment PERCENTAGE (40 persiapan / 80 pelaksanaan / 100 lulus
     * audit) aggregated with MAX across evidence. Keyed on "iso 27001" or
     * "sertifikasi internasional". Keep in sync with
     * PromptManager::isIsoCertification so the DB aggregation and the
     * prompt route the same measurements.
     */
    protected function isIsoCertification($measurement): bool
    {
        if (!$measurement) {
            return false;
        }

        $name = strtolower($measurement->measurement ?? '');
        $definition = strtolower($measurement->definition ?? '');

        foreach ([$name, $definition] as $haystack) {
            if (str_contains($haystack, 'iso 27001')
                || str_contains($haystack, 'iso/iec 27001')
                || str_contains($haystack, 'sertifikasi internasional')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the measurement is an "Implementasi Inisiatif Rencana
     * Strategis Teknologi Informasi (RSTI)" type, which counts the number of
     * REGISTERED roadmap initiatives whose status in the quarterly RSTI /
     * Master Plan TI monitoring report is "Selesai". Keyed on "rsti" or the
     * full "rencana strategis teknologi informasi" phrase. Keep in sync with
     * PromptManager::isRsti so the DB aggregation and the prompt route the
     * same measurements.
     */
    protected function isRstiMeasurement($measurement): bool
    {
        if (!$measurement) {
            return false;
        }

        $name = strtolower($measurement->measurement ?? '');
        $definition = strtolower($measurement->definition ?? '');

        foreach ([$name, $definition] as $haystack) {
            if (str_contains($haystack, 'rsti')
                || str_contains($haystack, 'rencana strategis teknologi informasi')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute the RSTI realisasi for a measurement + period from all
     * currently-valid ai_results.
     *
     * Each ai_result contributes `rsti_items` entries {code, name, status}
     * (already canonicalized by ResponseValidator). Initiatives are
     * de-duplicated by their roadmap CODE (uppercase, whitespace-stripped);
     * entries without a code fall back to the same token-based name matching
     * used for applications. When multiple evidence files disagree about the
     * same initiative, the most progressed (or terminal) status wins —
     * completion is monotonic, so a "Selesai" in a later report overrides an
     * "In Progress" in an earlier one within the same period.
     *
     * realisasi = count of unique initiatives whose status is EXACTLY
     * "Selesai".
     *
     * Fallbacks:
     *   - If no `rsti_items` exist in any evidence (legacy responses without
     *     the field), use MAX(realisasi) across all ai_results.
     *   - If no valid evidence at all, return 0.
     */
    protected function calculateRstiRealisasi(int $measurementId, string $quarter, int $year): float
    {
        $aiResults = AiResult::whereHas('upload', function ($q) use ($measurementId, $quarter, $year) {
            $q->where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year);
        })
            ->where('evidence_valid', true)
            ->get();

        // Rank terminal/progressed statuses higher so merges keep the most
        // decisive status for the initiative.
        $statusRank = [
            'Tidak Ditemukan' => 0,
            'Belum Berjalan' => 1,
            'In Progress' => 2,
            'Drop' => 3,
            'Selesai' => 4,
        ];

        // Clusters keyed by code ("B.1.3.4") or, for code-less entries, by
        // the sorted distinctive-token set of the initiative name.
        $clusters = [];

        foreach ($aiResults as $result) {
            $items = $result->rsti_items_array ?? [];

            foreach ($items as $item) {
                $name = is_string($item['name'] ?? null) ? trim($item['name']) : '';

                if ($name === '') {
                    continue;
                }

                $status = is_string($item['status'] ?? null) ? $item['status'] : '';
                $rank = $statusRank[$status] ?? 0;
                $code = is_string($item['code'] ?? null) ? trim($item['code']) : '';

                if ($code !== '') {
                    $clusterKey = 'code:' . strtoupper(preg_replace('/\s+/', '', $code));

                    if (!isset($clusters[$clusterKey]) || $rank > $clusters[$clusterKey]['rank']) {
                        $clusters[$clusterKey] = [
                            'name' => $name,
                            'status' => $status,
                            'rank' => $rank,
                        ];
                    }

                    continue;
                }

                // No code: cluster by initiative-name tokens (same matching
                // used for application de-duplication).
                $tokens = $this->extractApplicationTokens($name);

                if (empty($tokens['set'])) {
                    continue;
                }

                $matchedKey = null;
                foreach ($clusters as $key => $cluster) {
                    if (str_starts_with($key, 'code:')) {
                        continue;
                    }

                    if ($this->applicationTokensMatch($tokens, $cluster['tokens'])) {
                        $matchedKey = $key;
                        break;
                    }
                }

                if ($matchedKey === null) {
                    $clusters[$tokens['key']] = [
                        'name' => $name,
                        'status' => $status,
                        'rank' => $rank,
                        'tokens' => $tokens,
                    ];
                } elseif ($rank > $clusters[$matchedKey]['rank']) {
                    $clusters[$matchedKey]['name'] = $name;
                    $clusters[$matchedKey]['status'] = $status;
                    $clusters[$matchedKey]['rank'] = $rank;
                }
            }
        }

        // Fallback: no rsti_items in any evidence — use MAX(realisasi) from
        // all ai_results (legacy responses without rsti_items).
        if (empty($clusters)) {
            $maxRealisasi = $aiResults->max('realisasi');

            return (float) ($maxRealisasi ?? 0);
        }

        $completed = array_filter(
            $clusters,
            fn ($cluster) => $cluster['status'] === 'Selesai'
        );

        return (float) count($completed);
    }

    /**
     * Determine whether the measurement is a Capex realization / Realisasi
     * Nilai Investasi type, which aggregates investment line-items across
     * evidence files to compute an overall weighted realization percentage.
     */
    protected function isInvestmentRealisasi($measurement): bool
    {
        if (!$measurement) {
            return false;
        }

        $name = strtolower($measurement->measurement ?? '');
        $definition = strtolower($measurement->definition ?? '');
        $combined = $name . ' ' . $definition;

        if (str_contains($combined, 'capex')) {
            return true;
        }

        if (str_contains($combined, 'pengadaan')) {
            return true;
        }

        if (str_contains($combined, 'rkap')) {
            return true;
        }

        if (str_contains($name, 'realisasi') && str_contains($name, 'investasi')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the measurement is an SLA / infrastructure
     * availability KPI. Realisasi for these KPIs is a PERCENTAGE (mean
     * uptime across monitored targets), so the period aggregator must
     * AVERAGE — not SUM — the per-evidence realisasi values, otherwise
     * multi-evidence periods (e.g. "Percepatan proses pembayaran" with one
     * SLA Network report + one SLA Aplikasi report) would yield 200%.
     *
     * Detection mirrors PromptManager::getBasePrompt — it checks BOTH the
     * measurement name and its definition, because some SLA KPIs (e.g.
     * "Percepatan proses pembayaran (sharing KPI)") describe the SLA
     * requirement inside the definition rather than the measurement title.
     */
    protected function isSlaAvailabilityMeasurement($measurement): bool
    {
        if (!$measurement) {
            return false;
        }

        $name = strtolower($measurement->measurement ?? '');
        $definition = strtolower($measurement->definition ?? '');

        return $this->isSlaAvailabilityKeywords($name)
            || $this->isSlaAvailabilityKeywords($definition);
    }

    /**
     * Local keyword matcher kept in sync with PromptManager::isSlaAvailability
     * so both layers route the same measurements to SLA handling.
     */
    protected function isSlaAvailabilityKeywords(string $haystack): bool
    {
        $keywords = ['sla', 'uptime', 'availability', 'ketersediaan', 'infrastruktur', 'infrastructure'];

        foreach ($keywords as $kw) {
            if (str_contains($haystack, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute the period SLA availability realisasi. Aggregation strategy:
     *
     *   1. For each valid ai_result in the period, the AI has already
     *      returned a per-evidence realisasi = MEAN uptime across all
     *      `sla_targets` it identified in that evidence file (computed in
     *      PromptManager + validated by ResponseValidator). When
     *      `sla_targets` is present we re-derive the mean from it (the
     *      source of truth); otherwise we fall back to the ai_result's
     *      `realisasi` column (legacy responses without the breakdown).
     *
     *   2. Period realisasi = AVERAGE of those per-evidence means, because
     *      each evidence file is one component of the composite SLA
     *      (e.g. SLA Network report = one number, SLA Aplikasi report =
     *      another number). Summing them would breach 100% and produce
     *      meaningless achievements.
     *
     *   3. If no valid evidence exists for the period, return 0.
     */
    protected function calculateSlaAvailabilityRealisasi(int $measurementId, string $quarter, int $year): float
    {
        $aiResults = AiResult::whereHas('upload', function ($q) use ($measurementId, $quarter, $year) {
            $q->where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year);
        })
            ->where('evidence_valid', true)
            ->get();

        if ($aiResults->isEmpty()) {
            return 0;
        }

        $perEvidenceMeans = [];

        foreach ($aiResults as $result) {
            // Prefer the per-target breakdown (source of truth — captures
            // every monitored target the AI identified, not just the rounded
            // mean it returned).
            $targets = $result->sla_targets_array ?? [];

            if (!empty($targets)) {
                $uptimes = array_filter(
                    array_map(function ($t) {
                        return isset($t['uptime']) && is_numeric($t['uptime']) ? (float) $t['uptime'] : null;
                    }, $targets),
                    fn ($u) => $u !== null
                );

                if (!empty($uptimes)) {
                    $perEvidenceMeans[] = round(array_sum($uptimes) / count($uptimes), 2);
                    continue;
                }
            }

            // Fallback: use the realisasi column directly (legacy or AI
            // responses that did not include sla_targets but still produced
            // a coherent mean uptime as realisasi).
            $perEvidenceMeans[] = (float) ($result->realisasi ?? 0);
        }

        if (empty($perEvidenceMeans)) {
            return 0;
        }

        return round(array_sum($perEvidenceMeans) / count($perEvidenceMeans), 2);
    }

    /**
     * Determine whether the measurement is a "Pencapaian Project Management:
     * Traceability" type, which aggregates per-project lifecycle stage
     * progress across evidence files to compute an overall coverage
     * percentage. Keyed on "project management" or "traceability" in the
     * measurement name.
     */
    protected function isProjectManagementTraceability($measurement): bool
    {
        if (!$measurement) {
            return false;
        }

        $name = strtolower($measurement->measurement ?? '');

        return str_contains($name, 'project management')
            || str_contains($name, 'traceability');
    }

    /**
     * Compute the overall Project Management: Traceability coverage percentage
     * for a measurement + period from all currently-valid ai_results.
     *
     * Each ai_result contributes one or more `traceability_items` entries
     * {name, stage, achievement_pct}. Multiple evidence files for the SAME
     * project (e.g. a Kajian + a TOR for the same project) are de-duplicated
     * using the same token-based matching used for applications. For each
     * unique project, the HIGHEST achievement_pct across its evidence files
     * is kept (representing the latest lifecycle stage reached).
     *
     * The overall realisasi is the SIMPLE AVERAGE of the per-project max
     * achievement_pct. Rationale: this rewards breadth AND depth — having
     * one project at BAST (100) and another at Kajian (20) yields 60,
     * reflecting partial program-wide coverage.
     *
     * Fallbacks:
     *   - If no `traceability_items` exist in any evidence (e.g. legacy
     *     responses without the field), use MAX(realisasi) across all
     *     ai_results.
     *   - If no valid evidence at all, return 0.
     */
    protected function calculateTraceabilityCoverage(int $measurementId, string $quarter, int $year): float
    {
        $aiResults = AiResult::whereHas('upload', function ($q) use ($measurementId, $quarter, $year) {
            $q->where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year);
        })
            ->where('evidence_valid', true)
            ->get();

        // Each cluster is keyed by sorted distinctive-token set. We keep the
        // entry with the HIGHEST achievement_pct for each unique project so
        // that later lifecycle stages override earlier ones for the same
        // project.
        $clusters = [];

        foreach ($aiResults as $result) {
            $items = $result->traceability_items_array ?? [];

            foreach ($items as $item) {
                $name = $item['name'] ?? '';

                if (trim($name) === '') {
                    continue;
                }

                $tokens = $this->extractApplicationTokens($name);

                if (empty($tokens['set'])) {
                    continue;
                }

                $pct = isset($item['achievement_pct']) && is_numeric($item['achievement_pct'])
                    ? (float) $item['achievement_pct']
                    : 0;

                $matchedKey = null;
                foreach ($clusters as $key => $cluster) {
                    if ($this->applicationTokensMatch($tokens, $cluster['tokens'])) {
                        $matchedKey = $key;
                        break;
                    }
                }

                if ($matchedKey === null) {
                    $clusters[$tokens['key']] = [
                        'name' => $name,
                        'stage' => $item['stage'] ?? '',
                        'pct' => $pct,
                        'tokens' => $tokens,
                    ];
                } else {
                    // Merge: keep the entry with the HIGHEST achievement_pct
                    // (latest lifecycle stage wins).
                    if ($pct > $clusters[$matchedKey]['pct']) {
                        $clusters[$matchedKey]['name'] = $name;
                        $clusters[$matchedKey]['stage'] = $item['stage'] ?? '';
                        $clusters[$matchedKey]['pct'] = $pct;
                    }
                }
            }
        }

        // Fallback: no traceability_items in any evidence — use MAX(realisasi)
        // from all ai_results (legacy responses without traceability_items).
        if (empty($clusters)) {
            $maxRealisasi = $aiResults->max('realisasi');
            return (float) ($maxRealisasi ?? 0);
        }

        $perProjectMax = array_map(fn ($c) => $c['pct'], $clusters);

        return round(array_sum($perProjectMax) / count($perProjectMax), 2);
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
     * Compute the overall Capex realization percentage for a measurement +
     * period from all currently-valid ai_results.
     *
     * Collects all investment_items across all valid evidence, deduplicates
     * them by canonical name (same token-based matching used for application
     * deduplication), then computes:
     *   - Primary: sum(realized) / sum(budget) × 100 (weighted by budget)
     *   - Fallback: simple average of per-item percentages when no budget
     *     data is available
     *   - Last resort: MAX(realisasi) across all evidence (when no items at
     *     all were extracted — e.g. legacy responses without investment_items)
     */
    protected function calculateInvestmentRealisasi(int $measurementId, string $quarter, int $year): float
    {
        $aiResults = AiResult::whereHas('upload', function ($q) use ($measurementId, $quarter, $year) {
            $q->where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year);
        })
            ->where('evidence_valid', true)
            ->get();

        // Each cluster is keyed by sorted distinctive-token set. When the
        // same investment item appears in multiple evidence files (e.g. a
        // monitoring report + an individual SPK), we merge into a single
        // cluster and keep the entry with the most complete budget data.
        $clusters = [];

        foreach ($aiResults as $result) {
            $items = $result->investment_items_array ?? [];

            foreach ($items as $item) {
                $name = $item['name'] ?? '';

                if (trim($name) === '') {
                    continue;
                }

                $tokens = $this->extractApplicationTokens($name);

                if (empty($tokens['set'])) {
                    continue;
                }

                $matchedKey = null;
                foreach ($clusters as $key => $cluster) {
                    if ($this->applicationTokensMatch($tokens, $cluster['tokens'])) {
                        $matchedKey = $key;
                        break;
                    }
                }

                if ($matchedKey === null) {
                    $clusters[$tokens['key']] = [
                        'item' => $item,
                        'tokens' => $tokens,
                    ];
                } else {
                    // Merge: keep the entry with the most complete budget data.
                    $existing = $clusters[$matchedKey]['item'];
                    $newBudget = $item['budget'] ?? 0;
                    $existingBudget = $existing['budget'] ?? 0;

                    if ($newBudget > $existingBudget) {
                        $clusters[$matchedKey]['item'] = $item;
                    } elseif ($newBudget === $existingBudget) {
                        // Same budget — prefer the one with higher realized.
                        $newRealized = $item['realized'] ?? 0;
                        $existingRealized = $existing['realized'] ?? 0;
                        if ($newRealized > $existingRealized) {
                            $clusters[$matchedKey]['item'] = $item;
                        }
                    }
                }
            }
        }

        // Fallback: no investment_items in any evidence — use MAX(realisasi)
        // from all ai_results (legacy responses without investment_items).
        if (empty($clusters)) {
            $maxRealisasi = $aiResults->max('realisasi');
            return (float) ($maxRealisasi ?? 0);
        }

        $uniqueItems = array_map(fn ($c) => $c['item'], $clusters);

        $totalBudget = array_sum(array_map(fn ($i) => $i['budget'] ?? 0, $uniqueItems));
        $totalRealized = array_sum(array_map(fn ($i) => $i['realized'] ?? 0, $uniqueItems));

        if ($totalBudget > 0) {
            return round(($totalRealized / $totalBudget) * 100, 2);
        }

        // Fallback: simple average of per-item percentages when no budget
        // data is available.
        $percentages = array_filter(array_map(fn ($i) => $i['percentage'] ?? 0, $uniqueItems));

        if (!empty($percentages)) {
            return round(array_sum($percentages) / count($percentages), 2);
        }

        return 0;
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
