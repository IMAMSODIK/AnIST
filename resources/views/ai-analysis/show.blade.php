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

    {{-- Matched Initiative --}}
    {{-- @if($aiResult->matched_initiative)
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
    @endif --}}

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
