@extends('layouts.app')
@section('title', 'Measurements')
@section('page-title', 'Measurements')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">KPI Measurements</h2>
            <p class="text-slate-500 text-sm mt-1">Manage all KPI measurements and their configurations</p>
        </div>
        <a href="{{ route('measurements.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Measurement
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="perspective" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                <option value="">All Perspectives</option>
                @foreach(['Financial', 'Customer', 'Internal Process', 'Learning & Growth'] as $p)
                <option value="{{ $p }}" {{ request('perspective') == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search measurements..."
                   class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm flex-1 min-w-[200px]">
            <button type="submit" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-sm font-medium transition-colors">Filter</button>
            <a href="{{ route('measurements.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm">Reset</a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Perspective</th>
                        <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Objective</th>
                        <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Measurement</th>
                        {{-- <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Weight</th> --}}
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Formula</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Targets</th>
                        <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Initiatives</th>
                        <th class="text-right px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($measurements as $measurement)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium
                                {{ $measurement->perspective === 'Financial' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                                {{ $measurement->perspective === 'Customer' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                {{ $measurement->perspective === 'Internal Process' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                                {{ $measurement->perspective === 'Learning & Growth' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300' : '' }}">
                                {{ $measurement->perspective }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-700 dark:text-slate-300">{{ $measurement->objective }}</td>
                        <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">{{ $measurement->measurement }}</td>
                        {{-- <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400">{{ $measurement->weight }}%</td> --}}
                        <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400 text-xs">{{ $measurement->formula ?? '-' }}</td>
                        <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400">{{ $measurement->targets_count }}</td>
                        <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400">{{ $measurement->initiatives_count }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('measurements.show', $measurement) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('measurements.edit', $measurement) }}" class="p-1.5 text-slate-400 hover:text-amber-600 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('measurements.destroy', $measurement) }}" onsubmit="return confirm('Delete this measurement?')">
                                    @csrf @method('DELETE')
                                    <button class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-slate-500">No measurements found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($measurements->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">{{ $measurements->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
