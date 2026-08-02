@extends('layouts.app')
@section('title', 'Initiatives')
@section('page-title', 'Initiatives')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">KPI Initiatives</h2>
            <p class="text-slate-500 text-sm mt-1">Manage initiatives linked to each KPI measurement</p>
        </div>
        <a href="{{ route('initiatives.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Initiative
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="measurement_id" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                <option value="">All Measurements</option>
                @foreach($measurements as $m)<option value="{{ $m->id }}" {{ request('measurement_id') == $m->id ? 'selected' : '' }}>{{ $m->measurement }}</option>@endforeach
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search initiatives..." class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm flex-1 min-w-[200px]">
            <button type="submit" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-sm font-medium transition-colors">Filter</button>
            <a href="{{ route('initiatives.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm">Reset</a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Measurement</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Initiative</th>
                    <th class="text-right px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($initiatives as $initiative)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4 font-medium text-slate-800 dark:text-white whitespace-nowrap">{{ $initiative->measurement->measurement }}</td>
                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $initiative->initiative }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('initiatives.edit', $initiative) }}" class="p-1.5 text-slate-400 hover:text-amber-600 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                            <form method="POST" action="{{ route('initiatives.destroy', $initiative) }}" onsubmit="return confirm('Delete this initiative?')">@csrf @method('DELETE')<button class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-6 py-12 text-center text-slate-500">No initiatives found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($initiatives->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">{{ $initiatives->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
