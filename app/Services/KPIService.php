<?php

namespace App\Services;

use App\Models\Measurement;
use App\Models\Realisasi;
use App\Models\Score;
use App\Models\Target;
use Illuminate\Support\Facades\Log;

class KPIService
{
    public function __construct(
        protected ScoreCalculator $scoreCalculator,
    ) {}

    public function calculateScore(int $measurementId, string $quarter, int $year): ?Score
    {
        try {
            $measurement = Measurement::findOrFail($measurementId);

            $target = Target::where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year)
                ->first();

            $realisasi = Realisasi::where('measurement_id', $measurementId)
                ->where('quarter', $quarter)
                ->where('year', $year)
                ->first();

            if (!$target || !$realisasi) {
                return null;
            }

            $achievement = $this->calculateAchievement(
                $realisasi->value,
                $target->target,
                $measurement->formula
            );

            $score = $this->scoreCalculator->calculateFinalScore($achievement, $measurement->weight);

            return Score::updateOrCreate(
                [
                    'measurement_id' => $measurementId,
                    'quarter' => $quarter,
                    'year' => $year,
                ],
                [
                    'score' => $score,
                    'achievement' => $achievement,
                ]
            );
        } catch (\Exception $e) {
            Log::error('KPI calculation error', [
                'measurement_id' => $measurementId,
                'quarter' => $quarter,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function calculateAchievement(float $realisasi, float $target, ?string $formula): float
    {
        $achievement = match (strtolower($formula ?? 'higher is better')) {
            'higher is better' => $this->calculateHigherIsBetterAchievement($realisasi, $target),
            'lower is better' => $this->calculateLowerIsBetterAchievement($realisasi, $target),
            'exact target' => $this->calculateExactTargetAchievement($realisasi, $target),
            default => $this->calculateHigherIsBetterAchievement($realisasi, $target),
        };

        // Cap at 120 to align with the top score band.
        return min(120, $achievement);
    }

    /**
     * Achievement for "higher is better" KPIs.
     *
     * When target > 0: realisasi / target × 100.
     *
     * When target <= 0 (no delivery expected in this period — e.g. RSTI TW I-III
     * target = 0 initiatives): any realisasi is ON or AHEAD of schedule, so the
     * achievement is 100. This also guards the division-by-zero that previously
     * crashed the evidence-processing job.
     */
    protected function calculateHigherIsBetterAchievement(float $realisasi, float $target): float
    {
        if ($target <= 0) {
            return 100;
        }

        return ($realisasi / $target) * 100;
    }

    /**
     * Achievement for "exact target" KPIs, guarded against target = 0.
     */
    protected function calculateExactTargetAchievement(float $realisasi, float $target): float
    {
        if ($realisasi == $target) {
            return 100;
        }

        if ($target <= 0) {
            return 0;
        }

        return max(0, (1 - abs($realisasi - $target) / $target) * 100);
    }

    /**
     * Achievement for "lower is better" KPIs (e.g. incident count, downtime).
     *
     * When target > 0:
     *   - realisasi <= target : 100 + ((target - realisasi) / target) * 100
     *   - realisasi >  target : 100 - ((realisasi - target) / target) * 100 (floored at 0)
     *
     * When target <= 0 (zero-tolerance — e.g. "0 cybersecurity incidents"):
     *   - realisasi <= 0 : 100% (perfect, zero incidents)
     *   - realisasi >  0 :   0% (any incident = total failure)
     */
    protected function calculateLowerIsBetterAchievement(float $realisasi, float $target): float
    {
        if ($target <= 0) {
            return $realisasi <= 0 ? 100 : 0;
        }

        if ($realisasi <= $target) {
            return 100 + (($target - $realisasi) / $target) * 100;
        }

        return max(0, 100 - (($realisasi - $target) / $target) * 100);
    }

    public function calculateAllScores(string $quarter, int $year): array
    {
        $measurements = Measurement::all();
        $results = [];

        foreach ($measurements as $measurement) {
            $score = $this->calculateScore($measurement->id, $quarter, $year);
            if ($score) {
                $results[] = $score;
            }
        }

        return $results;
    }

    /**
     * Overall KPI score for a period, expressed as a weight-normalised value.
     *
     * Previously this summed raw weighted scores, which only produced a
     * meaningful number when measurement weights summed to exactly 100.
     * We now compute a weighted average of per-measurement achievement using
     * each measurement's weight, so the result stays in the 0–120 range
     * regardless of how weights are distributed.
     */
    public function getOverallScore(string $quarter, int $year): float
    {
        $row = Score::where('scores.quarter', $quarter)
            ->where('scores.year', $year)
            ->join('measurements', 'scores.measurement_id', '=', 'measurements.id')
            ->selectRaw('SUM(scores.achievement * measurements.weight) AS weighted')
            ->selectRaw('SUM(measurements.weight) AS total_weight')
            ->first();

        if (!$row || $row->total_weight <= 0) {
            return 0;
        }

        return round($row->weighted / $row->total_weight, 2);
    }

    public function getScoreByPerspective(string $quarter, int $year): array
    {
        return Score::where('scores.quarter', $quarter)
            ->where('scores.year', $year)
            ->join('measurements', 'scores.measurement_id', '=', 'measurements.id')
            ->select('measurements.perspective')
            ->selectRaw('SUM(scores.score) as total_score')
            ->selectRaw('AVG(scores.achievement) as avg_achievement')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('measurements.perspective')
            ->get()
            ->toArray();
    }
}
