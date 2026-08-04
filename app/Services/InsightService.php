<?php

namespace App\Services;

use App\AI\GeminiService;
use App\AI\PromptManager;
use App\AI\ResponseValidator;
use App\Models\AiResult;
use App\Models\AuditTrail;
use App\Models\KpiInsight;
use App\Models\Measurement;
use App\Models\Realisasi;
use App\Models\Score;
use App\Models\Target;
use Illuminate\Support\Facades\Log;

class InsightService
{
    public function __construct(
        protected GeminiService $gemini,
        protected PromptManager $promptManager,
        protected ResponseValidator $validator,
        protected ScoreCalculator $scoreCalculator,
    ) {}

    /**
     * Generate (or refresh) the narrative insight that explains WHY a KPI is
     * achieved or not, for a single measurement + period. Safe to call after
     * every evidence analysis so the insight always reflects the latest data.
     *
     * Returns null on any failure — never throws, so it cannot break the
     * upload/evidence pipeline (per AGENTS.md error-handling rules).
     */
    public function generateInsight(int $measurementId, string $quarter, int $year): ?KpiInsight
    {
        try {
            $measurement = Measurement::find($measurementId);

            if (!$measurement) {
                Log::warning('Insight skipped: measurement not found', [
                    'measurement_id' => $measurementId,
                ]);
                return null;
            }

            $target = Target::where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year)
                ->first();

            $realisasi = Realisasi::where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year)
                ->first();

            $score = Score::where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year)
                ->first();

            // Without a calculated score there is no final result to reason
            // about — wait until Laravel has computed one.
            if (!$score) {
                Log::info('Insight skipped: no score calculated yet', [
                    'measurement_id' => $measurementId,
                    'quarter' => $quarter,
                    'year' => $year,
                ]);
                return null;
            }

            $achievement = (float) $score->achievement;
            $status = $this->scoreCalculator->getStatus($achievement);
            $targetValue = (float) ($target?->target ?? 0);
            $realisasiValue = (float) ($realisasi?->value ?? 0);

            [$gap, $gapDirection] = $this->computeGap(
                $realisasiValue,
                $targetValue,
                $measurement->formula
            );

            // Pull ALL valid evidence analysis + matched initiatives for this
            // period to ground the narrative with complete context.
            [$evidenceAnalysis, $matchedInitiative] = $this->allEvidenceContext($measurementId, $quarter, $year);

            $context = [
                'target' => $targetValue,
                'realisasi' => $realisasiValue,
                'achievement' => $achievement,
                'status' => $status,
                'gap' => $gap,
                'gap_direction' => $gapDirection,
                'evidence_analysis' => $evidenceAnalysis,
                'matched_initiative' => $matchedInitiative,
            ];

            $prompt = $this->promptManager->generateInsightPrompt($measurement, $context);

            $response = $this->gemini->analyzeEvidence($prompt, null, null);

            $this->logAudit('request_insight', $measurementId, [
                'status' => $response['success'] ? 'ok' : 'failed',
                'quarter' => $quarter,
                'year' => $year,
            ]);

            if (!$response['success']) {
                Log::warning('Insight AI call failed', [
                    'measurement_id' => $measurementId,
                    'error' => $response['error'] ?? 'Unknown',
                ]);
                return null;
            }

            $validation = $this->validator->validateInsight($response['data']);

            if (!$validation['valid']) {
                Log::warning('Insight validation failed', [
                    'measurement_id' => $measurementId,
                    'errors' => $validation['errors'],
                ]);
                return null;
            }

            $data = $validation['data'];

            $insight = KpiInsight::updateOrCreate(
                [
                    'measurement_id' => $measurementId,
                    'quarter' => $quarter,
                    'year' => $year,
                ],
                [
                    'achieved_reason' => $data['achieved_reason'],
                    'not_achieved_reason' => $data['not_achieved_reason'],
                    'recommendations' => $data['recommendations'],
                    'raw_json' => $response['raw_response'] ?? null,
                ]
            );

            $this->logAudit('insight_generated', $measurementId, [
                'kpi_insight_id' => $insight->id,
                'status' => $status,
            ]);

            return $insight;

        } catch (\Exception $e) {
            // Per AGENTS.md: never let an AI failure break the broader flow.
            Log::error('Insight generation failed', [
                'measurement_id' => $measurementId,
                'quarter' => $quarter,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Gap between realisasi and target plus a human-readable direction.
     * Direction is relative to what is "good" for this formula type.
     */
    protected function computeGap(float $realisasi, float $target, ?string $formula): array
    {
        $gap = round(abs($realisasi - $target), 2);

        // When gap is zero, the result matches the target exactly — regardless
        // of formula type. This avoids confusing messages like "0 di bawah target".
        if ($gap == 0) {
            return [$gap, 'Tepat sesuai target'];
        }

        if (strtolower($formula ?? 'higher is better') === 'lower is better') {
            // For "lower is better", being under target is a surplus of good
            // performance; going over is the problem.
            $direction = $realisasi <= $target
                ? "{$gap} di bawah target (lebih baik)"
                : "{$gap} di atas target (melebihi batas)";
            return [$gap, $direction];
        }

        $direction = $realisasi >= $target
            ? "{$gap} di atas target"
            : "{$gap} di bawah target";
        return [$gap, $direction];
    }

    /**
     * Combined validated evidence analysis and matched initiatives for the
     * measurement + period. Instead of only the latest evidence, this gathers
     * ALL valid evidence so the insight narrative can explain the status of
     * each application / initiative individually.
     *
     * @return array{0:string, 1:string} [combinedAnalysis, primaryInitiative]
     */
    protected function allEvidenceContext(int $measurementId, string $quarter, int $year): array
    {
        $aiResults = AiResult::whereHas('upload', function ($q) use ($measurementId, $quarter, $year) {
            $q->where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year);
        })
            ->where('evidence_valid', true)
            ->latest()
            ->get();

        if ($aiResults->isEmpty()) {
            return ['', ''];
        }

        // Build a combined analysis block — each evidence is labelled with
        // its source file name and matched initiative so Gemini can reason
        // about every application / initiative separately.
        $combinedAnalysis = $aiResults->map(function ($r) {
            $fileName = $r->upload?->file_name ?? 'Unknown';
            $initiative = $r->matched_initiative ?? 'N/A';
            $realisasi = $r->realisasi ?? 0;
            $recommendations = $r->recommendations_array;
            $applications = $r->applications_array;
            $goLiveApps = $r->go_live_applications_array;

            $block = "### Evidence: {$fileName}";
            $block .= "\n- **Initiative**: {$initiative}";
            $block .= "\n- **Realisasi dari AI**: {$realisasi}";

            if (!empty($applications)) {
                $goLiveSet = array_map('strtolower', $goLiveApps);
                $labelled = array_map(function ($app) use ($goLiveSet) {
                    $isGoLive = in_array(strtolower($app), $goLiveSet, true);
                    return $app . ($isGoLive ? ' [Go Live]' : ' [UAT/Testing]');
                }, $applications);
                $block .= "\n- **Applications identified**: " . implode(', ', $labelled);
            }

            $block .= "\n- **Analysis**: {$r->analysis}";

            if (!empty($recommendations)) {
                $block .= "\n- **Recommendations**: " . implode('; ', $recommendations);
            }

            return $block;
        })->join("\n\n---\n\n");

        // Use the first (most recent) initiative as the primary label.
        $primaryInitiative = (string) ($aiResults->first()->matched_initiative ?? '');

        return [$combinedAnalysis, $primaryInitiative];
    }

    protected function logAudit(string $action, int $measurementId, array $newValues = []): void
    {
        try {
            AuditTrail::create([
                'user_id' => request()->user()?->id,
                'action' => $action,
                'model_type' => Measurement::class,
                'model_id' => $measurementId,
                'new_values' => $newValues,
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ?? null,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log insight audit trail', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
