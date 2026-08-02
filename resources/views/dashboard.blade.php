@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Executive Dashboard</h2>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">AI-Based KPI Monitoring & Evidence Validation</p>
        </div>
        <form method="GET" class="flex items-center gap-3">
            <select name="year" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-indigo-200 outline-none">
                @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="quarter" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-indigo-200 outline-none">
                @foreach(['Q1','Q2','Q3','Q4'] as $q)
                <option value="{{ $q }}" {{ $quarter == $q ? 'selected' : '' }}>{{ $q }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total KPI</p>
                    <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $widgets['total_kpi'] }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>


        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">KPI Achieved</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-1">{{ $widgets['kpi_achieved'] }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>


        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">On Progress</p>
                    <p class="text-3xl font-bold text-amber-600 mt-1">{{ $widgets['kpi_on_progress'] }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>


        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Below Target</p>
                    <p class="text-3xl font-bold text-rose-600 mt-1">{{ $widgets['kpi_below_target'] }}</p>
                </div>
                <div class="w-12 h-12 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">AI Analyses Today</p>
                    <p class="text-3xl font-bold text-violet-600 mt-1">{{ $widgets['ai_analyses_today'] }}</p>
                </div>
                <div class="w-12 h-12 bg-violet-100 dark:bg-violet-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pending Analysis</p>
                    <p class="text-3xl font-bold text-orange-600 mt-1">{{ $widgets['pending_analysis'] }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Avg. Achievement</p>
                    <p class="text-3xl font-bold text-cyan-600 mt-1">{{ $widgets['average_achievement'] }}%</p>
                </div>
                <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Evidence Uploaded</p>
                    <p class="text-3xl font-bold text-teal-600 mt-1">{{ $widgets['evidence_uploaded'] }}</p>
                </div>
                <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">KPI Achievement per Quarter</h3>
            <canvas id="quarterlyChart" height="250"></canvas>
        </div>


        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Perspective Performance</h3>
            <canvas id="perspectiveChart" height="250"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">KPI Trend</h3>
            <canvas id="kpiTrendChart" height="200"></canvas>
        </div>


        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Upload Activity</h3>
            <canvas id="uploadActivityChart" height="200"></canvas>
        </div>


        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Initiative Progress</h3>
            <canvas id="initiativeProgressChart" height="200"></canvas>
        </div>
    </div> --}}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">AI Confidence Distribution</h3>
            <canvas id="confidenceChart" height="200"></canvas>
        </div> --}}


        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('uploads.create') }}" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span class="text-sm font-medium">Upload Evidence</span>
                </a>
                <a href="{{ route('ai-analysis.index') }}" class="flex items-center gap-3 px-4 py-3 bg-violet-50 dark:bg-violet-900/20 rounded-xl text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="text-sm font-medium">View AI Analysis</span>
                </a>
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-sm font-medium">View Reports</span>
                </a>
                <a href="{{ route('kpi-monitoring.index') }}" class="flex items-center gap-3 px-4 py-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span class="text-sm font-medium">Manage KPI</span>
                </a>
            </div>
        </div>


        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Recent Activity</h3>
            <div class="space-y-4">
                @forelse($recentUploads as $upload)
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $upload->status === 'completed' ? 'bg-emerald-100 text-emerald-600' : ($upload->status === 'failed' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 dark:text-white truncate">{{ $upload->file_name }}</p>
                        <p class="text-xs text-slate-500">{{ $upload->measurement->measurement ?? 'N/A' }} &middot; {{ $upload->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full font-medium
                        {{ $upload->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($upload->status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ ucfirst($upload->status) }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-slate-500 text-center py-4">No recent uploads</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
{{-- <script>
document.addEventListener('DOMContentLoaded', function() {
    // Quarterly Achievement Chart
    const quarterlyCtx = document.getElementById('quarterlyChart');
    if (quarterlyCtx) {
        new Chart(quarterlyCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(collect($quarterlyAchievement)->pluck('quarter')) !!},
                datasets: [{
                    label: 'Achievement (%)',
                    data: {!! json_encode(collect($quarterlyAchievement)->pluck('achievement')) !!},
                    backgroundColor: ['rgba(99, 102, 241, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)', 'rgba(244, 63, 94, 0.8)'],
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 120, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Perspective Performance Chart
    const perspectiveCtx = document.getElementById('perspectiveChart');
    if (perspectiveCtx) {
        const perspectiveData = @json($perspectivePerformance);
        new Chart(perspectiveCtx, {
            type: 'radar',
            data: {
                labels: perspectiveData.map(p => p.perspective),
                datasets: [{
                    label: 'Achievement (%)',
                    data: perspectiveData.map(p => p.avg_achievement),
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { r: { beginAtZero: true, max: 120 } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // Confidence Distribution Chart
    const confidenceCtx = document.getElementById('confidenceChart');
    if (confidenceCtx) {
        const confData = @json($confidenceDistribution);
        new Chart(confidenceCtx, {
            type: 'doughnut',
            data: {
                labels: ['High (>80%)', 'Medium (50-80%)', 'Low (<50%)'],
                datasets: [{
                    data: [confData.high, confData.medium, confData.low],
                    backgroundColor: ['rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)', 'rgba(244, 63, 94, 0.8)'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } } }
            }
        });
    }

    // KPI Trend Chart (Line)
    const kpiTrendCtx = document.getElementById('kpiTrendChart');
    if (kpiTrendCtx) {
        const trendData = @json($kpiTrend);
        new Chart(kpiTrendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(t => t.quarter),
                datasets: [{
                    label: 'Avg Score',
                    data: trendData.map(t => t.score),
                    borderColor: 'rgba(99, 102, 241, 1)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                    pointRadius: 4,
                }, {
                    label: 'Avg Achievement (%)',
                    data: trendData.map(t => t.achievement),
                    borderColor: 'rgba(16, 185, 129, 1)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } } },
                scales: {
                    y: { beginAtZero: true, max: 120, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Upload Activity Chart (Area)
    const uploadActivityCtx = document.getElementById('uploadActivityChart');
    if (uploadActivityCtx) {
        const activityData = @json($uploadActivity);
        new Chart(uploadActivityCtx, {
            type: 'line',
            data: {
                labels: activityData.map(a => a.date),
                datasets: [{
                    label: 'Uploads',
                    data: activityData.map(a => a.count),
                    borderColor: 'rgba(139, 92, 246, 1)',
                    backgroundColor: 'rgba(139, 92, 246, 0.15)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(139, 92, 246, 1)',
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } }
                }
            }
        });
    }

    // Initiative Progress Chart (Horizontal Bar)
    const initiativeCtx = document.getElementById('initiativeProgressChart');
    if (initiativeCtx) {
        const initData = @json($initiativeProgress);
        new Chart(initiativeCtx, {
            type: 'bar',
            data: {
                labels: initData.map(i => i.perspective),
                datasets: [{
                    label: 'Matched',
                    data: initData.map(i => i.matched),
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false,
                }, {
                    label: 'Remaining',
                    data: initData.map(i => i.total - i.matched),
                    backgroundColor: 'rgba(226, 232, 240, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } } },
                scales: {
                    x: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    y: { stacked: true, grid: { display: false } }
                }
            }
        });
    }
});
</script> --}}
@endpush
@endsection
