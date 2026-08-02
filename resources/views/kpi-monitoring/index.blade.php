@extends('layouts.app')
@section('title', 'KPI Monitoring')
@section('page-title', 'KPI Monitoring')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">KPI Monitoring</h2>
            <p class="text-slate-500 text-sm mt-1">Track KPI performance across all perspectives</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-3">
                <select name="year" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <select name="quarter" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
                    @foreach(['Q1','Q2','Q3','Q4'] as $q)
                    <option value="{{ $q }}" {{ $quarter == $q ? 'selected' : '' }}>{{ $q }}</option>
                    @endforeach
                </select>
            </form>
            <form method="POST" action="{{ route('kpi-monitoring.recalculate') }}">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="quarter" value="{{ $quarter }}">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Recalculate
                </button>
            </form>
        </div>
    </div>

    {{-- Overall Score --}}
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-indigo-200 text-sm">Overall KPI Score</p>
                <p class="text-4xl font-bold mt-1">{{ number_format($overallScore, 2) }}</p>
                <p class="text-indigo-200 text-sm mt-1">{{ $quarter }} {{ $year }}</p>
            </div>
            <div class="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
        </div>
    </div>

    {{-- Perspective Summary --}}
    @if(count($perspectiveScores) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($perspectiveScores as $ps)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 card-hover">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $ps['perspective'] }}</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ number_format($ps['total_score'], 2) }}</p>
            <p class="text-xs text-slate-400 mt-1">Avg Achievement: {{ number_format($ps['avg_achievement'], 1) }}% &middot; {{ $ps['count'] }} KPIs</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- KPI Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Perspective</th>
                        <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Measurement</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Weight</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Target</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Realisasi</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Achievement</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Score</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Status</th>
                        <th class="text-right px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($kpiData as $kpi)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium
                                {{ $kpi['measurement']->perspective === 'Financial' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                {{ $kpi['measurement']->perspective === 'Customer' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                {{ $kpi['measurement']->perspective === 'Internal Process' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                                {{ $kpi['measurement']->perspective === 'Learning & Growth' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300' : '' }}">
                                {{ $kpi['measurement']->perspective }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">{{ $kpi['measurement']->measurement }}</td>
                        <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400">{{ $kpi['measurement']->weight }}%</td>
                        <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400">{{ number_format($kpi['target'], 2) }}</td>
                        <td class="px-6 py-4 text-center font-medium text-indigo-600 dark:text-indigo-400">{{ number_format($kpi['realisasi'], 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $kpi['status_color'] === 'emerald' ? 'bg-emerald-500' : ($kpi['status_color'] === 'amber' ? 'bg-amber-500' : ($kpi['status_color'] === 'orange' ? 'bg-orange-500' : 'bg-rose-500')) }}" style="width: {{ min($kpi['achievement'], 120) / 1.2 }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ number_format($kpi['achievement'], 1) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center font-semibold text-slate-800 dark:text-white">{{ number_format($kpi['score'], 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $kpi['status_color'] === 'emerald' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                {{ $kpi['status_color'] === 'amber' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                                {{ $kpi['status_color'] === 'orange' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' : '' }}
                                {{ $kpi['status_color'] === 'rose' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : '' }}">
                                {{ $kpi['status'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('kpi-monitoring.show', ['measurement' => $kpi['measurement'], 'year' => $year]) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 transition-colors inline-block">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-6 py-12 text-center text-slate-500">No KPI data available for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
