<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Models\Score;
use App\Services\DashboardService;
use App\Services\InsightService;
use App\Services\KPIService;
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected KPIService $kpiService,
        protected DashboardService $dashboardService,
        protected ScoreCalculator $scoreCalculator,
        protected InsightService $insightService,
    ) {}

    public function index(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $quarter = $request->get('quarter', 'Q' . ceil(date('n') / 3));

        $measurements = Measurement::with([
            'targets' => fn($q) => $q->where('year', $year)->where('quarter', $quarter),
            'realisasis' => fn($q) => $q->where('year', $year)->where('quarter', $quarter),
            'scores' => fn($q) => $q->where('year', $year)->where('quarter', $quarter),
            'kpiInsights' => fn($q) => $q->where('year', $year)->where('quarter', $quarter),
        ])->orderBy('perspective')->orderBy('objective')->get();

        $reportData = $measurements->map(function ($m) {
            $target = $m->targets->first();
            $realisasi = $m->realisasis->first();
            $score = $m->scores->first();
            $insight = $m->kpiInsights->first();

            return [
                'measurement_id' => $m->id,
                'perspective' => $m->perspective,
                'objective' => $m->objective,
                'measurement' => $m->measurement,
                'weight' => $m->weight,
                'unit' => $m->unit,
                'target' => $target?->target ?? 0,
                'realisasi' => $realisasi?->value ?? 0,
                'achievement' => $score?->achievement ?? 0,
                'score' => $score?->score ?? 0,
                'status' => $this->scoreCalculator->getStatus($score?->achievement ?? 0),
                'achieved_reason' => $insight?->achieved_reason ?? '',
                'not_achieved_reason' => $insight?->not_achieved_reason ?? '',
                'recommendations' => $insight?->recommendations_array ?? [],
                'has_insight' => filled($insight?->achieved_reason) || filled($insight?->not_achieved_reason),
            ];
        });

        $overallScore = $this->kpiService->getOverallScore($quarter, $year);
        $perspectivePerformance = $this->dashboardService->getPerspectivePerformance($year, $quarter);

        return view('reports.index', compact(
            'year', 'quarter', 'reportData', 'overallScore', 'perspectivePerformance'
        ));
    }

    /**
     * Manually (re)generate the AI insight for a single KPI + period. Used to
     * backfill older periods that were processed before insights existed, or
     * to refresh one row on demand.
     */
    public function regenerate(Request $request)
    {
        $validated = $request->validate([
            'measurement_id' => ['required', 'integer', 'exists:measurements,id'],
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $insight = $this->insightService->generateInsight(
            $validated['measurement_id'],
            $validated['quarter'],
            $validated['year']
        );

        $queryParams = [
            'year' => $validated['year'],
            'quarter' => $validated['quarter'],
        ];

        if ($insight) {
            return redirect()->route('reports.index', $queryParams)
                ->with('success', 'AI insight berhasil diperbarui.');
        }

        return redirect()->route('reports.index', $queryParams)
            ->with('error', 'Gagal membuat AI insight. Pastikan KPI sudah memiliki target, realisasi, dan score.');
    }
}
