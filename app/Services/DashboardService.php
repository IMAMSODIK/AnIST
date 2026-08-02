<?php

namespace App\Services;

use App\Models\AiResult;
use App\Models\Measurement;
use App\Models\Realisasi;
use App\Models\Score;
use App\Models\Target;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected ScoreCalculator $scoreCalculator,
    ) {}

    public function getWidgets(int $year, string $quarter): array
    {
        $measurements = Measurement::count();

        $scores = Score::where('year', $year)
            ->where('quarter', $quarter)
            ->get();

        $achieved = $scores->where('achievement', '>=', 100)->count();
        $onProgress = $scores->filter(fn($s) => $s->achievement >= 60 && $s->achievement < 100)->count();
        $belowTarget = $scores->where('achievement', '<', 60)->count();

        $aiToday = AiResult::whereDate('created_at', today())->count();
        $pendingAnalysis = Upload::where('status', 'pending')->count();
        $avgAchievement = $scores->avg('achievement') ?? 0;
        $avgScore = $scores->avg('score') ?? 0;
        $evidenceUploaded = Upload::where('year', $year)->where('quarter', $quarter)->count();

        return [
            'total_kpi' => $measurements,
            'kpi_achieved' => $achieved,
            'kpi_on_progress' => $onProgress,
            'kpi_below_target' => $belowTarget,
            'ai_analyses_today' => $aiToday,
            'pending_analysis' => $pendingAnalysis,
            'average_achievement' => round($avgAchievement, 2),
            'average_score' => round($avgScore, 2),
            'evidence_uploaded' => $evidenceUploaded,
        ];
    }

    public function getQuarterlyAchievement(int $year): array
    {
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $data = [];

        foreach ($quarters as $quarter) {
            $avgAchievement = Score::where('year', $year)
                ->where('quarter', $quarter)
                ->avg('achievement') ?? 0;

            $data[] = [
                'quarter' => $quarter,
                'achievement' => round($avgAchievement, 2),
            ];
        }

        return $data;
    }

    public function getPerspectivePerformance(int $year, string $quarter): array
    {
        return DB::table('scores')
            ->join('measurements', 'scores.measurement_id', '=', 'measurements.id')
            ->where('scores.year', $year)
            ->where('scores.quarter', $quarter)
            ->select('measurements.perspective')
            ->selectRaw('AVG(scores.achievement) as avg_achievement')
            ->selectRaw('SUM(scores.score) as total_score')
            ->selectRaw('COUNT(*) as kpi_count')
            ->groupBy('measurements.perspective')
            ->get()
            ->map(fn($item) => [
                'perspective' => $item->perspective,
                'avg_achievement' => round($item->avg_achievement, 2),
                'total_score' => round($item->total_score, 2),
                'kpi_count' => $item->kpi_count,
                'status' => $this->scoreCalculator->getStatus($item->avg_achievement),
                'color' => $this->scoreCalculator->getStatusColor($item->avg_achievement),
            ])
            ->toArray();
    }

    /**
     * Distribution of AI confidence across high/medium/low buckets.
     *
     * Buckets use half-open intervals so every row is counted exactly once,
     * including failed analyses whose confidence is 0 (previously uncounted):
     *   low:   confidence <= 50
     *   medium: 50 < confidence < 80
     *   high:   confidence >= 80
     */
    public function getConfidenceDistribution(?int $year = null, ?string $quarter = null): array
    {
        $query = AiResult::query();

        // Narrow to the selected period when provided, consistent with the
        // other dashboard windows.
        if ($year) {
            $query->whereHas('upload', fn ($q) => $q->where('year', $year));
        }
        if ($quarter) {
            $query->whereHas('upload', fn ($q) => $q->where('quarter', $quarter));
        }

        return [
            'high' => (clone $query)->where('confidence', '>=', 80)->count(),
            'medium' => (clone $query)->where('confidence', '>', 50)->where('confidence', '<', 80)->count(),
            'low' => (clone $query)->where('confidence', '<=', 50)->count(),
        ];
    }

    /**
     * Initiative progress per perspective: how many of a perspective's
     * measurements have at least one validated, AI-matched evidence in the
     * selected period. Used by the "Initiative Progress" dashboard chart.
     */
    public function getInitiativeProgress(int $year, string $quarter): array
    {
        // Eager-load only the uploads that belong to this period and carry a
        // validated AI result; presence of such an upload means the
        // measurement's initiative has been matched.
        $measurements = Measurement::with(['uploads' => function ($q) use ($year, $quarter) {
            $q->where('year', $year)->where('quarter', $quarter)
                ->whereHas('aiResult', fn ($r) => $r->where('evidence_valid', true));
        }])->orderBy('perspective')->get();

        return $measurements
            ->groupBy('perspective')
            ->map(fn ($items, $perspective) => [
                'perspective' => $perspective,
                'total' => $items->count(),
                'matched' => $items->filter(fn ($m) => $m->uploads->isNotEmpty())->count(),
            ])
            ->values()
            ->map(fn ($item) => array_merge($item, [
                'progress' => $item['total'] > 0 ? round(($item['matched'] / $item['total']) * 100, 2) : 0,
            ]))
            ->toArray();
    }

    public function getRecentUploads(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return Upload::with(['measurement', 'user', 'aiResult'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentAnalyses(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return AiResult::with(['upload.measurement'])
            ->whereNotNull('analysis')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUploadActivity(int $days = 30): array
    {
        return Upload::where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getKPITrend(int $measurementId, int $year): array
    {
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $data = [];

        foreach ($quarters as $quarter) {
            $score = Score::where('measurement_id', $measurementId)
                ->where('year', $year)
                ->where('quarter', $quarter)
                ->first();

            $target = Target::where('measurement_id', $measurementId)
                ->where('year', $year)
                ->where('quarter', $quarter)
                ->first();

            $realisasi = Realisasi::where('measurement_id', $measurementId)
                ->where('year', $year)
                ->where('quarter', $quarter)
                ->first();

            $data[] = [
                'quarter' => $quarter,
                'target' => $target?->target,
                'realisasi' => $realisasi?->value,
                'achievement' => $score?->achievement,
                'score' => $score?->score,
            ];
        }

        return $data;
    }

    /**
     * Overall KPI score trend per quarter for the given year.
     * Used by the "KPI Trend" dashboard chart.
     */
    public function getOverallKPITrend(int $year): array
    {
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $data = [];

        foreach ($quarters as $quarter) {
            $avgScore = Score::where('year', $year)
                ->where('quarter', $quarter)
                ->avg('score') ?? 0;

            $avgAchievement = Score::where('year', $year)
                ->where('quarter', $quarter)
                ->avg('achievement') ?? 0;

            $data[] = [
                'quarter' => $quarter,
                'score' => round($avgScore, 2),
                'achievement' => round($avgAchievement, 2),
            ];
        }

        return $data;
    }
}
