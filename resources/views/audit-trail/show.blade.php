@extends('layouts.app')
@section('title', 'Audit Detail')
@section('page-title', 'Audit Detail')

@section('content')
<div class="space-y-6">
    <a href="{{ route('audit-trail.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Audit Trail
    </a>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6">Audit Trail Detail</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Timestamp</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $auditTrail->created_at->format('d M Y H:i:s') }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">User</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $auditTrail->user->name ?? 'System' }}</p>
                <p class="text-xs text-slate-400">{{ $auditTrail->user->email ?? '' }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Action</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ str_replace('_', ' ', ucfirst($auditTrail->action)) }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Model</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">
                    @if($auditTrail->model_type)
                    {{ class_basename($auditTrail->model_type) }} #{{ $auditTrail->model_id }}
                    @else
                    N/A
                    @endif
                </p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">IP Address</p>
                <p class="text-sm font-mono text-slate-800 dark:text-white">{{ $auditTrail->ip_address ?? 'N/A' }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">User Agent</p>
                <p class="text-xs text-slate-600 dark:text-slate-300 break-all">{{ Str::limit($auditTrail->user_agent, 100) ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    @if($auditTrail->old_values)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Old Values</h3>
        <pre class="bg-slate-900 text-slate-300 rounded-xl p-4 text-xs overflow-x-auto max-h-64">{{ json_encode($auditTrail->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif

    @if($auditTrail->new_values)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">New Values</h3>
        <pre class="bg-slate-900 text-slate-300 rounded-xl p-4 text-xs overflow-x-auto max-h-64">{{ json_encode($auditTrail->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif
</div>
@endsection
