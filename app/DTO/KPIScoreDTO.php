<?php

namespace App\DTO;

class KPIScoreDTO
{
    public function __construct(
        public readonly int $measurementId,
        public readonly string $quarter,
        public readonly int $year,
        public readonly float $target,
        public readonly float $realisasi,
        public readonly float $achievement,
        public readonly float $score,
        public readonly string $status,
        public readonly string $statusColor,
    ) {}

    public function toArray(): array
    {
        return [
            'measurement_id' => $this->measurementId,
            'quarter' => $this->quarter,
            'year' => $this->year,
            'target' => $this->target,
            'realisasi' => $this->realisasi,
            'achievement' => $this->achievement,
            'score' => $this->score,
            'status' => $this->status,
            'status_color' => $this->statusColor,
        ];
    }
}
