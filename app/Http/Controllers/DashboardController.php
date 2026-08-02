<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function index(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $quarter = $request->get('quarter', 'Q' . ceil(date('n') / 3));

        $widgets = $this->dashboardService->getWidgets($year, $quarter);
        $quarterlyAchievement = $this->dashboardService->getQuarterlyAchievement($year);
        $perspectivePerformance = $this->dashboardService->getPerspectivePerformance($year, $quarter);
        $confidenceDistribution = $this->dashboardService->getConfidenceDistribution();
        $recentUploads = $this->dashboardService->getRecentUploads();
        $recentAnalyses = $this->dashboardService->getRecentAnalyses();
        $uploadActivity = $this->dashboardService->getUploadActivity();
        $kpiTrend = $this->dashboardService->getOverallKPITrend($year);
        $initiativeProgress = $this->dashboardService->getInitiativeProgress($year, $quarter);

        return view('dashboard', compact(
            'year',
            'quarter',
            'widgets',
            'quarterlyAchievement',
            'perspectivePerformance',
            'confidenceDistribution',
            'recentUploads',
            'recentAnalyses',
            'uploadActivity',
            'kpiTrend',
            'initiativeProgress',
        ));
    }
}
