@extends('layouts.app')
@section('title', $measurement->measurement . ' - KPI Detail')
@section('page-title', 'KPI Detail')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('kpi-monitoring.index', ['year' => $year]) }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to KPI Monitoring
        </a>
        <form method="GET" class="flex items-center gap-2">
            <select name="year" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    {{-- Header --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ $measurement->measurement }}</h2>
                <p class="text-slate-500 mt-1">{{ $measurement->objective }}</p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-lg text-xs font-medium
                {{ $measurement->perspective === 'Financial' ? 'bg-emerald-100 text-emerald-700' : '' }}
                {{ $measurement->perspective === 'Customer' ? 'bg-blue-100 text-blue-700' : '' }}
                {{ $measurement->perspective === 'Internal Process' ? 'bg-amber-100 text-amber-700' : '' }}
                {{ $measurement->perspective === 'Learning & Growth' ? 'bg-violet-100 text-violet-700' : '' }}">
                {{ $measurement->perspective }}
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Formula</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $measurement->formula ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Unit</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $measurement->unit ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Weight</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $measurement->weight }}%</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Year</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $year }}</p>
            </div>
        </div>
    </div>

    {{-- Trend Chart --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Quarterly Trend {{ $year }}</h3>
        <canvas id="trendChart" height="280"></canvas>
    </div>

    {{-- Quarterly Data Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Quarterly Breakdown</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Quarter</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Target</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Realisasi</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Achievement</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($trend as $t)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                    <td class="px-6 py-4 text-center font-medium text-slate-800 dark:text-white">{{ $t['quarter'] }}</td>
                    <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400">{{ $t['target'] !== null ? number_format($t['target'], 2) : '-' }}</td>
                    <td class="px-6 py-4 text-center font-medium text-indigo-600 dark:text-indigo-400">{{ $t['realisasi'] !== null ? number_format($t['realisasi'], 2) : '-' }}</td>
                    <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400">{{ $t['achievement'] !== null ? number_format($t['achievement'], 1) . '%' : '-' }}</td>
                    <td class="px-6 py-4 text-center font-semibold text-slate-800 dark:text-white">{{ $t['score'] !== null ? number_format($t['score'], 2) : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Initiatives --}}
    @if($measurement->initiatives->count())
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Initiatives</h3>
        <ul class="space-y-2">
            @foreach($measurement->initiatives as $initiative)
            <li class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl">
                <svg class="w-5 h-5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="text-sm text-slate-700 dark:text-slate-300">{{ $initiative->initiative }}</span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const trendData = @json($trend);
    const ctx = document.getElementById('trendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.map(t => t.quarter),
                datasets: [
                    {
                        label: 'Target',
                        data: trendData.map(t => t.target),
                        borderColor: 'rgba(99, 102, 241, 0.5)',
                        backgroundColor: 'rgba(99, 102, 241, 0.05)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 4,
                        tension: 0.3,
                        fill: false,
                    },
                    {
                        label: 'Realisasi',
                        data: trendData.map(t => t.realisasi),
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2.5,
                        pointRadius: 5,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        tension: 0.3,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 20 } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
