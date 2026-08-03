@extends('layouts.app')
@section('title', 'Evidence Detail')
@section('page-title', 'Evidence Detail')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('uploads.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Uploads
        </a>
        @if($upload->status === 'failed')
        <form method="POST" action="{{ route('uploads.retry', $upload) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-xl text-sm font-medium hover:bg-amber-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Retry Analysis
            </button>
        </form>
        @endif
    </div>

    {{-- File Info --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">{{ $upload->file_name }}</h2>
                    <p class="text-sm text-slate-500">{{ $upload->measurement->measurement ?? 'N/A' }} &middot; {{ $upload->quarter }} {{ $upload->year }}</p>
                </div>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium
                {{ $upload->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : '' }}
                {{ $upload->status === 'processing' ? 'bg-blue-100 text-blue-700' : '' }}
                {{ $upload->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                {{ $upload->status === 'failed' ? 'bg-rose-100 text-rose-700' : '' }}">
                {{ ucfirst($upload->status) }}
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-3">
                <p class="text-xs text-slate-500 mb-1">File Size</p>
                <p class="text-sm font-medium text-slate-800 dark:text-white">{{ number_format($upload->file_size / 1024, 1) }} KB</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-3">
                <p class="text-xs text-slate-500 mb-1">File Type</p>
                <p class="text-sm font-medium text-slate-800 dark:text-white">{{ strtoupper(pathinfo($upload->file_name, PATHINFO_EXTENSION)) }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-3">
                <p class="text-xs text-slate-500 mb-1">Uploaded By</p>
                <p class="text-sm font-medium text-slate-800 dark:text-white">{{ $upload->user->name ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-3">
                <p class="text-xs text-slate-500 mb-1">Uploaded At</p>
                <p class="text-sm font-medium text-slate-800 dark:text-white">{{ $upload->created_at->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>

    {{-- AI Analysis Result --}}
    @if($upload->aiResult)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            AI Analysis Result
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">
            {{-- <div class="bg-{{ $upload->aiResult->evidence_valid ? 'emerald' : 'rose' }}-50 dark:bg-{{ $upload->aiResult->evidence_valid ? 'emerald' : 'rose' }}-900/20 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Evidence Valid</p>
                <p class="text-lg font-bold {{ $upload->aiResult->evidence_valid ? 'text-emerald-600' : 'text-rose-600' }}">{{ $upload->aiResult->evidence_valid ? 'Yes' : 'No' }}</p>
            </div> --}}
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Realisasi</p>
                <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $upload->aiResult->realisasi ?? 0 }}</p>
            </div>
            {{-- <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Confidence</p>
                <p class="text-lg font-bold text-slate-800 dark:text-white">{{ $upload->aiResult->confidence ?? 0 }}%</p>
            </div> --}}
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Processing Time</p>
                <p class="text-lg font-bold text-slate-800 dark:text-white">{{ $upload->aiResult->processing_time ?? 0 }}s</p>
            </div>
        </div>

        @if($upload->aiResult->matched_initiative)
        <div class="mb-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Matched Initiative</p>
            <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">{{ $upload->aiResult->matched_initiative }}</p>
        </div>
        @endif

        @if($upload->aiResult->analysis)
        <div class="mb-4">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Analysis</p>
            <div class="p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ $upload->aiResult->analysis }}</div>
        </div>
        @endif

        @if($upload->aiResult->recommendation)
        @php $recommendations = json_decode($upload->aiResult->recommendation, true) ?? []; @endphp
        @if(count($recommendations) > 0)
        <div>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Recommendations</p>
            <ul class="space-y-2">
                @foreach($recommendations as $rec)
                <li class="flex items-start gap-2 p-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl">
                    <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $rec }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @endif

        @if($upload->aiResult->error_message)
        <div class="mt-4 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl">
            <p class="text-sm font-medium text-rose-700 dark:text-rose-300 mb-1">Error Message</p>
            <p class="text-sm text-rose-600 dark:text-rose-400">{{ $upload->aiResult->error_message }}</p>
        </div>
        @endif
    </div>
    @else
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 text-center">
        <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <p class="text-slate-500 dark:text-slate-400">AI analysis is {{ $upload->status === 'processing' ? 'in progress...' : 'pending.' }}</p>
    </div>
    @endif
</div>
@endsection
