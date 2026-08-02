@extends('layouts.app')
@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Audit Trail</h2>
        <p class="text-slate-500 text-sm mt-1">Complete log of all system activities</p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="action" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                <option value="">All Actions</option>
                @foreach($actions as $a)
                <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($a)) }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm" placeholder="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm" placeholder="To">
            <button type="submit" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-sm font-medium transition-colors">Filter</button>
            <a href="{{ route('audit-trail.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm">Reset</a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Timestamp</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">User</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Action</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Model</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">IP Address</th>
                    <th class="text-right px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($trails as $trail)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                        <p class="text-sm">{{ $trail->created_at->format('d M Y') }}</p>
                        <p class="text-xs text-slate-400">{{ $trail->created_at->format('H:i:s') }}</p>
                    </td>
                    <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">{{ $trail->user->name ?? 'System' }}</td>
                    <td class="px-6 py-4">
                        @php
                        $actionColors = [
                            'upload_evidence' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                            'request_ai' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
                            'response_ai' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                            'validate_result' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
                            'calculate_kpi' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                            'create_measurement' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
                            'update_measurement' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                            'delete_measurement' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                            'delete_evidence' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                        ];
                        $color = $actionColors[$trail->action] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
                        @endphp
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium {{ $color }}">
                            {{ str_replace('_', ' ', ucfirst($trail->action)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                        @if($trail->model_type)
                        {{ class_basename($trail->model_type) }} #{{ $trail->model_id }}
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 font-mono">{{ $trail->ip_address ?? '-' }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('audit-trail.show', $trail) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 transition-colors inline-block">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No audit trail records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($trails->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">{{ $trails->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
