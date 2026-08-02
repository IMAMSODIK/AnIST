<?php

namespace App\Services;

class ScoreCalculator
{
    /**
     * Score bands defined as half-open intervals [min, max).
     *
     * Using `>= min && < max` instead of `<= max` with `.99` caps avoids
     * floating-point gaps: an achievement like 99.997 previously matched no
     * band (it is > 99.99 yet < 100) and silently fell through to score 0.
     * Bands are sorted descending so the first match wins.
     */
    protected array $scoreBands = [
        ['min' => 120, 'max' => INF, 'score' => 120],
        ['min' => 110, 'max' => 120, 'score' => 110],
        ['min' => 100, 'max' => 110, 'score' => 100],
        ['min' => 90, 'max' => 100, 'score' => 90],
        ['min' => 80, 'max' => 90, 'score' => 80],
        ['min' => 70, 'max' => 80, 'score' => 70],
        ['min' => 60, 'max' => 70, 'score' => 60],
        ['min' => 0, 'max' => 60, 'score' => 50],
    ];

    public function mapToScoreBand(float $achievement): float
    {
        // Negative achievement is floored to 0 so it lands in the lowest band.
        $achievement = max(0, $achievement);

        foreach ($this->scoreBands as $band) {
            if ($achievement >= $band['min'] && $achievement < $band['max']) {
                return $band['score'];
            }
        }

        // Fallback to the lowest band's score for safety.
        return $this->scoreBands[count($this->scoreBands) - 1]['score'];
    }

    public function calculateFinalScore(float $achievement, float $weight): float
    {
        $bandScore = $this->mapToScoreBand($achievement);
        return round(($bandScore * $weight) / 100, 2);
    }

    public function getStatus(float $achievement): string
    {
        if ($achievement >= 100) return 'Achieved';
        if ($achievement >= 80) return 'On Track';
        if ($achievement >= 60) return 'Needs Improvement';
        return 'Below Target';
    }

    public function getStatusColor(float $achievement): string
    {
        if ($achievement >= 100) return 'emerald';
        if ($achievement >= 80) return 'amber';
        if ($achievement >= 60) return 'orange';
        return 'rose';
    }
}
