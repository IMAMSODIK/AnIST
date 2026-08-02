<?php

namespace App\Http\Controllers;

use App\Jobs\CalculateKPIJob;
use App\Models\Measurement;
use App\Models\Realisasi;
use App\Models\Score;
use App\Models\Target;
use App\Services\DashboardService;
use App\Services\KPIService;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;

class KPIMonitoringController extends Controller
{
    public function __construct(
        protected KPIService $kpiService,
        protected DashboardService $dashboardService,
        protected ScoreCalculator $scoreCalculator,
    ) {}

    public function index(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $quarter = $request->get('quarter', 'Q' . ceil(date('n') / 3));

        $measurements = Measurement::with([
            'targets' => fn($q) => $q->where('year', $year)->where('quarter', $quarter),
            'realisasis' => fn($q) => $q->where('year', $year)->where('quarter', $quarter),
            'scores' => fn($q) => $q->where('year', $year)->where('quarter', $quarter),
        ])->orderBy('perspective')->orderBy('objective')->get();

        $kpiData = $measurements->map(function ($m) use ($year, $quarter) {
            $target = $m->targets->first();
            $realisasi = $m->realisasis->first();
            $score = $m->scores->first();

            return [
                'measurement' => $m,
                'target' => $target?->target ?? 0,
                'realisasi' => $realisasi?->value ?? 0,
                'achievement' => $score?->achievement ?? 0,
                'score' => $score?->score ?? 0,
                'status' => $this->scoreCalculator->getStatus($score?->achievement ?? 0),
                'status_color' => $this->scoreCalculator->getStatusColor($score?->achievement ?? 0),
            ];
        });

        $overallScore = $this->kpiService->getOverallScore($quarter, $year);
        $perspectiveScores = $this->kpiService->getScoreByPerspective($quarter, $year);

        return view('kpi-monitoring.index', compact(
            'year', 'quarter', 'kpiData', 'overallScore', 'perspectiveScores'
        ));
    }

    public function show(Request $request, Measurement $measurement)
    {
        $year = (int) $request->get('year', date('Y'));
        $trend = $this->dashboardService->getKPITrend($measurement->id, $year);

        $measurement->load(['targets', 'initiatives', 'uploads.aiResult']);

        return view('kpi-monitoring.show', compact('measurement', 'year', 'trend'));
    }

    public function recalculate(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $quarter = $request->get('quarter', 'Q' . ceil(date('n') / 3));

        // Run asynchronously on the queue by default so the dashboard request
        // doesn't block; fall back to a synchronous run with ?sync=1.
        if ($request->boolean('sync')) {
            $this->kpiService->calculateAllScores($quarter, $year);
            $message = 'KPI scores recalculated successfully.';
        } else {
            CalculateKPIJob::dispatch($quarter, $year)->onQueue('default');
            $message = 'KPI recalculation has been queued. Scores will refresh shortly.';
        }

        return redirect()->route('kpi-monitoring.index', ['year' => $year, 'quarter' => $quarter])
            ->with('success', $message);
    }
}
