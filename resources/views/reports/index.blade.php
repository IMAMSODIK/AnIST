@extends('layouts.app')
@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">KPI Report</h2>
            <p class="text-slate-500 text-sm mt-1">Comprehensive KPI performance report</p>
        </div>
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
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-2xl p-6 text-white">
            <p class="text-indigo-200 text-sm">Overall Score</p>
            <p class="text-4xl font-bold mt-1">{{ number_format($overallScore, 2) }}</p>
            <p class="text-indigo-200 text-sm mt-1">{{ $quarter }} {{ $year }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total KPIs</p>
            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $reportData->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Across {{ $reportData->pluck('perspective')->unique()->count() }} perspectives</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Avg Achievement</p>
            @php $avgAch = $reportData->avg('achievement'); @endphp
            <p class="text-3xl font-bold {{ $avgAch >= 100 ? 'text-emerald-600' : ($avgAch >= 80 ? 'text-amber-600' : 'text-rose-600') }} mt-1">{{ number_format($avgAch, 1) }}%</p>
            <p class="text-xs text-slate-400 mt-1">{{ $reportData->where('achievement', '>=', 100)->count() }} achieved target</p>
        </div>
    </div>

    {{-- Perspective Performance --}}
    @if(count($perspectivePerformance) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($perspectivePerformance as $pp)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $pp['perspective'] }}</p>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pp['color'] }}-100 text-{{ $pp['color'] }}-700 dark:bg-{{ $pp['color'] }}-900/30 dark:text-{{ $pp['color'] }}-300">{{ $pp['status'] }}</span>
            </div>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($pp['total_score'], 2) }}</p>
            <div class="mt-2 w-full bg-slate-200 dark:bg-slate-600 rounded-full h-1.5">
                <div class="h-1.5 rounded-full bg-{{ $pp['color'] }}-500" style="width: {{ min($pp['avg_achievement'], 100) }}%"></div>
            </div>
            <p class="text-xs text-slate-500 mt-1">{{ number_format($pp['avg_achievement'], 1) }}% avg &middot; {{ $pp['kpi_count'] }} KPIs</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Detailed Report Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Detailed KPI Report - {{ $quarter }} {{ $year }}</h3>
            <p class="text-xs text-slate-400 mt-1">Klik baris untuk melihat insight AI mengapa KPI tercapai / belum tercapai.</p>
        </div>
        <div class="overflow-x-auto">
            {{-- Single Alpine scope on the body tracks which rows are expanded. --}}
            <table class="w-full text-sm" x-data="{ openRows: {} }" x-cloak>
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Perspective</th>
                        <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Objective</th>
                        <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Measurement</th>
                        {{-- <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Weight</th> --}}
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Unit</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Target</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Realisasi</th>
                        {{-- <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Achievement</th> --}}
                        {{-- <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Score</th> --}}
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Status</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">AI Insight</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @php $currentPerspective = ''; @endphp
                    @forelse($reportData as $kpi)
                    @php
                        $isExpandedKey = "'" . $kpi['measurement_id'] . "'";
                        $isAchieved = $kpi['achievement'] >= 100;
                    @endphp
                    @if($kpi['perspective'] !== $currentPerspective)
                    @php $currentPerspective = $kpi['perspective']; @endphp
                    <tr class="bg-slate-50/50 dark:bg-slate-700/20">
                        <td colspan="11" class="px-6 py-2">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ $currentPerspective }}</span>
                        </td>
                    </tr>
                    @endif
                    <tr class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                        @click="openRows[{{ $isExpandedKey }}] = !openRows[{{ $isExpandedKey }}]">
                        <td class="px-6 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $kpi['perspective'] }}</td>
                        <td class="px-6 py-3 text-slate-600 dark:text-slate-300 text-xs">{{ $kpi['objective'] }}</td>
                        <td class="px-6 py-3 font-medium text-slate-800 dark:text-white">{{ $kpi['measurement'] }}</td>
                        {{-- <td class="px-6 py-3 text-center text-slate-600 dark:text-slate-400">{{ $kpi['weight'] }}%</td> --}}
                        <td class="px-6 py-3 text-center text-slate-500 dark:text-slate-400 text-xs">{{ $kpi['unit'] }}</td>
                        <td class="px-6 py-3 text-center text-slate-600 dark:text-slate-400">{{ number_format($kpi['target'], 2) }}</td>
                        <td class="px-6 py-3 text-center font-medium text-indigo-600 dark:text-indigo-400">{{ number_format($kpi['realisasi'], 2) }}</td>
                        {{-- <td class="px-6 py-3 text-center text-slate-600 dark:text-slate-400">{{ number_format($kpi['achievement'], 1) }}%</td> --}}
                        {{-- <td class="px-6 py-3 text-center font-semibold text-slate-800 dark:text-white">{{ number_format($kpi['score'], 2) }}</td> --}}
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $kpi['status'] === 'Achieved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                {{ $kpi['status'] === 'On Track' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                                {{ $kpi['status'] === 'Needs Improvement' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' : '' }}
                                {{ $kpi['status'] === 'Below Target' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : '' }}">
                                {{ $kpi['status'] }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($kpi['has_insight'])
                            <button type="button"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-colors
                                {{ $isAchieved ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/40' : 'bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-900/20 dark:text-rose-300 dark:hover:bg-rose-900/40' }}">
                                <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                    :class="openRows[{{ $isExpandedKey }}] ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                                Insight
                            </button>
                            @else
                            <form method="POST" action="{{ route('reports.insight-regenerate') }}" class="inline"
                                  onclick="event.stopPropagation()">
                                @csrf
                                <input type="hidden" name="measurement_id" value="{{ $kpi['measurement_id'] }}">
                                <input type="hidden" name="quarter" value="{{ $quarter }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-500 hover:bg-indigo-100 hover:text-indigo-600 dark:bg-slate-700 dark:text-slate-400 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-300 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Generate
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    {{-- Expandable insight row --}}
                    <tr x-show="openRows[{{ $isExpandedKey }}]" x-transition
                        x-cloak class="bg-slate-50/60 dark:bg-slate-700/20">
                        <td colspan="11" class="px-6 py-5">
                            <div class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-5">
                                @if($kpi['has_insight'])
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-sm font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                        AI Insight
                                    </h4>
                                    <form method="POST" action="{{ route('reports.insight-regenerate') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="measurement_id" value="{{ $kpi['measurement_id'] }}">
                                        <input type="hidden" name="quarter" value="{{ $quarter }}">
                                        <input type="hidden" name="year" value="{{ $year }}">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-300 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Regenerate
                                        </button>
                                    </form>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    {{-- Reason achieved --}}
                                    <div class="rounded-xl border p-4
                                        {{ $isAchieved ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/40 dark:bg-emerald-900/10' : 'border-slate-200 bg-slate-50 dark:border-slate-600 dark:bg-slate-700/20' }}">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="w-6 h-6 rounded-full flex items-center justify-center
                                                {{ $isAchieved ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-200 text-slate-500 dark:bg-slate-600 dark:text-slate-300' }}">
                                                @if($isAchieved)
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @endif
                                            </span>
                                            <p class="text-sm font-semibold {{ $isAchieved ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-300' }}">
                                                Alasan KPI {{ $isAchieved ? 'Tercapai' : 'On Track' }}
                                            </p>
                                        </div>
                                        @if($kpi['achieved_reason'])
                                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $kpi['achieved_reason'] }}</p>
                                        @else
                                        <p class="text-sm text-slate-400 italic">Belum ada faktor pencapaian yang teridentifikasi.</p>
                                        @endif
                                    </div>

                                    {{-- Reason not achieved --}}
                                    <div class="rounded-xl border p-4
                                        {{ $isAchieved ? 'border-slate-200 bg-slate-50 dark:border-slate-600 dark:bg-slate-700/20' : 'border-rose-200 bg-rose-50/60 dark:border-rose-900/40 dark:bg-rose-900/10' }}">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="w-6 h-6 rounded-full flex items-center justify-center
                                                {{ $isAchieved ? 'bg-slate-200 text-slate-500 dark:bg-slate-600 dark:text-slate-300' : 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            </span>
                                            <p class="text-sm font-semibold {{ $isAchieved ? 'text-slate-600 dark:text-slate-300' : 'text-rose-700 dark:text-rose-300' }}">
                                                Alasan KPI Belum Tercapai
                                            </p>
                                        </div>
                                        @if($kpi['not_achieved_reason'])
                                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $kpi['not_achieved_reason'] }}</p>
                                        @else
                                        <p class="text-sm text-slate-400 italic">
                                            {{ $isAchieved ? 'KPI sudah tercapai — tidak ada gap tersisa.' : 'Belum ada analisa gap tersedia.' }}
                                        </p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Recommendations --}}
                                @if(count($kpi['recommendations']) > 0)
                                <div class="mt-4">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        Rekomendasi
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($kpi['recommendations'] as $i => $rec)
                                        <div class="flex items-start gap-3 p-3 bg-indigo-50 dark:bg-indigo-900/10 rounded-xl">
                                            <span class="w-5 h-5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $i + 1 }}</span>
                                            <p class="text-sm text-slate-700 dark:text-slate-300">{{ $rec }}</p>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                @else
                                {{-- No insight yet --}}
                                <div class="text-center py-6">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 mb-3">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                    </div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">Belum ada AI insight untuk KPI ini.</p>
                                    <form method="POST" action="{{ route('reports.insight-regenerate') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="measurement_id" value="{{ $kpi['measurement_id'] }}">
                                        <input type="hidden" name="quarter" value="{{ $quarter }}">
                                        <input type="hidden" name="year" value="{{ $year }}">
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Generate AI Insight
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="px-6 py-12 text-center text-slate-500">No report data available.</td></tr>
                    @endforelse
                </tbody>
                @if($reportData->count() > 0)
                {{-- <tfoot class="bg-slate-50 dark:bg-slate-700/50 font-semibold">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-slate-800 dark:text-white">Total</td>
                        <td class="px-6 py-3 text-center text-slate-800 dark:text-white">{{ number_format($reportData->sum('weight'), 1) }}%</td>
                        <td class="px-6 py-3"></td>
                        <td class="px-6 py-3"></td>
                        <td class="px-6 py-3"></td>
                        <td class="px-6 py-3 text-center text-slate-800 dark:text-white">{{ number_format($reportData->avg('achievement'), 1) }}%</td>
                        <td class="px-6 py-3 text-center text-indigo-600 dark:text-indigo-400">{{ number_format($overallScore, 2) }}</td>
                        <td class="px-6 py-3"></td>
                        <td class="px-6 py-3"></td>
                    </tr>
                </tfoot> --}}
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
