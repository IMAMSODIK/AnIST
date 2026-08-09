@extends('layouts.app')
@section('title', 'AI Analysis Detail')
@section('page-title', 'AI Analysis Detail')

@section('content')
<div class="space-y-6">
    <a href="{{ route('ai-analysis.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to AI Analysis
    </a>

    {{-- Header --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">{{ $aiResult->upload->measurement->measurement ?? 'N/A' }}</h2>
                <p class="text-sm text-slate-500 mt-1">Evidence: {{ $aiResult->upload->file_name ?? 'N/A' }} &middot; {{ $aiResult->upload->quarter ?? '' }} {{ $aiResult->upload->year ?? '' }}</p>
            </div>
            {{-- <div class="flex items-center gap-2">
                @if($aiResult->evidence_valid)
                <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-xl text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Evidence Valid
                </span>
                @else
                <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-xl text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Evidence Invalid
                </span>
                @endif
            </div> --}}
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Realisasi</p>
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $aiResult->realisasi ?? 0 }}</p>
            </div>
            {{-- <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Confidence</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $aiResult->confidence ?? 0 }}%</p>
            </div> --}}
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Processing Time</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $aiResult->processing_time ?? 0 }}s</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Analyzed</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $aiResult->created_at->format('d M Y') }}</p>
                <p class="text-xs text-slate-500">{{ $aiResult->created_at->format('H:i:s') }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Uploaded By</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $aiResult->upload->user->name ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    {{-- Applications Identified (used to de-duplicate evidence for the same
         application across multiple uploads). --}}
    @php $applications = $aiResult->applications_array ?? []; @endphp
    @php $goLiveApps = $aiResult->go_live_applications_array ?? []; @endphp
    @if(count($applications) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            Applications Identified
        </h3>
        <div class="flex flex-wrap gap-2">
            @php $goLiveSet = array_map('strtolower', $goLiveApps); @endphp
            @foreach($applications as $app)
                @php $isGoLive = in_array(strtolower($app), $goLiveSet, true); @endphp
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-sm font-medium border {{ $isGoLive ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border-emerald-100 dark:border-emerald-800/50' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border-amber-100 dark:border-amber-800/50' }}">
                    {{ $app }}
                    @if($isGoLive)
                        <span class="text-xs">[Go Live]</span>
                    @else
                        <span class="text-xs">[UAT/Testing]</span>
                    @endif
                </span>
            @endforeach
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">* Hanya aplikasi yang sudah Go Live yang dihitung ke realisasi. UAT/Testing hanya untuk audit trail.</p>
    </div>
    @endif

    {{-- Investment Items (Capex Realization) --}}
    @php $investmentItems = $aiResult->investment_items_array ?? []; @endphp
    @if(count($investmentItems) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
            Investment Items (Capex)
        </h3>
        <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-700">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-slate-600 dark:text-slate-300">Item / FA</th>
                        <th class="text-right px-4 py-3 font-medium text-slate-600 dark:text-slate-300">Budget</th>
                        <th class="text-right px-4 py-3 font-medium text-slate-600 dark:text-slate-300">Realized</th>
                        <th class="text-center px-4 py-3 font-medium text-slate-600 dark:text-slate-300">%</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600 dark:text-slate-300">Stage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @php
                        $fmt = function ($n) { return is_numeric($n) ? 'Rp ' . number_format((float) $n, 0, ',', '.') : '-'; };
                        $pctColor = function ($p) {
                            if ($p >= 80) return 'bg-emerald-500';
                            if ($p >= 50) return 'bg-amber-500';
                            return 'bg-rose-500';
                        };
                    @endphp
                    @foreach($investmentItems as $item)
                        @php $pct = (float) ($item['percentage'] ?? 0); @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800 dark:text-white">{{ $item['name'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $fmt($item['budget'] ?? 0) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $fmt($item['realized'] ?? 0) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $pctColor($pct) }}" style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">{{ $item['status'] ?? '-' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">* Realisasi keseluruhan dihitung Laravel sebagai sum(realized)/sum(budget) x 100 dari seluruh item unik.</p>
    </div>
    @endif

    {{-- SLA Targets (Availability) --}}
    @php $slaTargets = $aiResult->sla_targets_array ?? []; @endphp
    @if(count($slaTargets) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3zm12-3c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3z"/></svg>
            SLA Targets (Availability)
        </h3>
        <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-700">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-slate-600 dark:text-slate-300">Target</th>
                        <th class="text-center px-4 py-3 font-medium text-slate-600 dark:text-slate-300">Uptime</th>
                        <th class="text-center px-4 py-3 font-medium text-slate-600 dark:text-slate-300">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($slaTargets as $target)
                        @php
                            $up = (float) ($target['uptime'] ?? 0);
                            $upColor = $up >= 99 ? 'bg-emerald-500' : ($up >= 92 ? 'bg-amber-500' : 'bg-rose-500');
                            $status = $up >= 99 ? 'Achieved' : ($up >= 92 ? 'Below SLA' : 'Critical');
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800 dark:text-white">{{ $target['name'] ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $upColor }}" style="width: {{ min($up, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $up }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $up >= 99 ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : ($up >= 92 ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' : 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300') }}">{{ $status }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">* Realisasi period = rata-rata uptime semua target.</p>
    </div>
    @endif

    {{-- Traceability Items (Project Lifecycle) --}}
    @php $traceItems = $aiResult->traceability_items_array ?? []; @endphp
    @if(count($traceItems) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Project Lifecycle (Traceability)
        </h3>
        <div class="space-y-3">
            @php
                $stageColor = [
                    'Kajian'       => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                    'TOR'          => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                    'SPK'          => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
                    'Implementasi' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
                    'BAST'         => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                    // 3-stage EA Project Management lifecycle (OMTI 2026 #7)
                    'Perencanaan'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                    'Development'  => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
                ];
            @endphp
            @foreach($traceItems as $item)
                @php
                    $stage = $item['stage'] ?? '';
                    $pct = (float) ($item['achievement_pct'] ?? 0);
                    $badge = $stageColor[$stage] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
                @endphp
                <div class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-slate-800 dark:text-white">{{ $item['name'] ?? '-' }}</p>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $badge }}">{{ $stage }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-violet-500" style="width: {{ min($pct, 100) }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $pct }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">* Stage mapping: 5-stage Traceability — Kajian=20, TOR=40, SPK=60, Implementasi=80, BAST=100. 3-stage EA (OMTI 2026 #7) — Perencanaan=25, Development=80, Implementasi(BAST)=100. Realisasi period = rata-rata achievement_pct per proyek unik.</p>
    </div>
    @endif

    {{-- Matched Initiative --}}
    @if($aiResult->matched_initiative)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Matched Initiative
        </h3>
        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
            <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">{{ $aiResult->matched_initiative }}</p>
            <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1">Confidence: {{ $aiResult->confidence }}%</p>
        </div>
    </div>
    @endif

    {{-- Analysis --}}
    @if($aiResult->analysis)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Analysis
        </h3>
        <div class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $aiResult->analysis }}</div>
    </div>
    @endif

    {{-- Recommendations --}}
    @php $recommendations = json_decode($aiResult->recommendation, true) ?? []; @endphp
    @if(count($recommendations) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            AI Recommendations
        </h3>
        <div class="space-y-2">
            @foreach($recommendations as $i => $rec)
            <div class="flex items-start gap-3 p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-xl">
                <span class="w-6 h-6 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $i + 1 }}</span>
                <p class="text-sm text-slate-700 dark:text-slate-300">{{ $rec }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Error --}}
    @if($aiResult->error_message)
    <div class="bg-rose-50 dark:bg-rose-900/10 rounded-2xl border border-rose-200 dark:border-rose-800 p-6">
        <h3 class="text-lg font-semibold text-rose-700 dark:text-rose-300 mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Error Details
        </h3>
        <p class="text-sm text-rose-600 dark:text-rose-400">{{ $aiResult->error_message }}</p>
    </div>
    @endif

    {{-- Raw JSON (collapsible) --}}
    @if($aiResult->raw_json)
    <div x-data="{ open: false }" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <button @click="open = !open" class="w-full flex items-center justify-between p-6 text-left">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                Raw AI Response
            </h3>
            <svg class="w-5 h-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-transition class="px-6 pb-6">
            <pre class="bg-slate-900 text-slate-300 rounded-xl p-4 text-xs overflow-x-auto max-h-96">{{ json_encode($aiResult->raw_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @endif
</div>
@endsection
