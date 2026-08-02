<?php

namespace App\Jobs;

use App\Models\AuditTrail;
use App\Services\KPIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateKPIJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public string $quarter,
        public int $year,
        public ?int $measurementId = null,
    ) {}

    public function handle(KPIService $kpiService): void
    {
        Log::info('Calculating KPI scores', [
            'quarter' => $this->quarter,
            'year' => $this->year,
            'measurement_id' => $this->measurementId,
        ]);

        try {
            if ($this->measurementId) {
                $kpiService->calculateScore($this->measurementId, $this->quarter, $this->year);
            } else {
                $kpiService->calculateAllScores($this->quarter, $this->year);
            }

            AuditTrail::create([
                'user_id' => null,
                'action' => 'calculate_kpi',
                'new_values' => [
                    'quarter' => $this->quarter,
                    'year' => $this->year,
                    'measurement_id' => $this->measurementId,
                    'type' => $this->measurementId ? 'single' : 'all',
                ],
            ]);

            Log::info('KPI scores calculated successfully');
        } catch (\Exception $e) {
            Log::error('KPI calculation job failed', [
                'error' => $e->getMessage(),
                'quarter' => $this->quarter,
                'year' => $this->year,
            ]);
            throw $e;
        }
    }
}
