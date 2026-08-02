@extends('layouts.app')
@section('title', $measurement->measurement)
@section('page-title', 'Measurement Detail')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('measurements.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Measurements
        </a>
        <a href="{{ route('measurements.edit', $measurement) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-xl text-sm font-medium hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start justify-between mb-6">
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
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Evidence Count</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $measurement->uploads->count() }}</p>
            </div>
        </div>
        @if($measurement->definition)
        <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl">
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Definition</p>
            <p class="text-sm text-slate-700 dark:text-slate-300">{{ $measurement->definition }}</p>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Targets</h3>
            @if($measurement->targets->count())
            <div class="grid grid-cols-2 gap-3">
                @foreach($measurement->targets->sortBy(['year','quarter']) as $target)
                <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $target->year }} {{ $target->quarter }}</p>
                    <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($target->target, 2) }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $measurement->unit }}</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500 text-center py-6">No targets defined.</p>
            @endif
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Initiatives</h3>
            @if($measurement->initiatives->count())
            <ul class="space-y-3">
                @foreach($measurement->initiatives as $initiative)
                <li class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl">
                    <svg class="w-5 h-5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $initiative->initiative }}</span>
                </li>
                @endforeach
            </ul>
            @else
            <p class="text-sm text-slate-500 text-center py-6">No initiatives defined.</p>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Recent Evidence</h3>
        @if($measurement->uploads->count())
        <div class="space-y-3">
            @foreach($measurement->uploads->sortByDesc('created_at')->take(5) as $upload)
            <a href="{{ route('uploads.show', $upload) }}" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $upload->status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-900/30' : ($upload->status === 'failed' ? 'bg-rose-100 dark:bg-rose-900/30' : 'bg-amber-100 dark:bg-amber-900/30') }}">
                        <svg class="w-5 h-5 {{ $upload->status === 'completed' ? 'text-emerald-600' : ($upload->status === 'failed' ? 'text-rose-600' : 'text-amber-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-white">{{ $upload->file_name }}</p>
                        <p class="text-xs text-slate-500">{{ $upload->quarter }} {{ $upload->year }} &middot; {{ $upload->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                <span class="text-xs px-2 py-1 rounded-full font-medium {{ $upload->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($upload->status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($upload->status) }}</span>
            </a>
            @endforeach
        </div>
        @else
        <p class="text-sm text-slate-500 text-center py-6">No evidence uploaded yet.</p>
        @endif
    </div>
</div>
@endsection
