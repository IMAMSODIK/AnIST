@extends('layouts.app')
@section('title', 'Upload Evidence')
@section('page-title', 'Upload Evidence')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Evidence Uploads</h2>
            <p class="text-slate-500 text-sm mt-1">Upload and track evidence for AI analysis</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('uploads.batch-process') }}" onsubmit="return confirm('Process all pending evidence with AI? This will queue them for analysis.')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Process All Pending
                </button>
            </form>
            <a href="{{ route('uploads.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Upload Evidence
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="measurement_id" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                <option value="">All Measurements</option>
                @foreach($measurements as $m)<option value="{{ $m->id }}" {{ request('measurement_id') == $m->id ? 'selected' : '' }}>{{ $m->measurement }}</option>@endforeach
            </select>
            <select name="status" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                <option value="">All Status</option>
                @foreach(['pending','processing','completed','failed'] as $s)<option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <select name="quarter" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                <option value="">All Quarters</option>
                @foreach(['Q1','Q2','Q3','Q4'] as $q)<option value="{{ $q }}" {{ request('quarter') == $q ? 'selected' : '' }}>{{ $q }}</option>@endforeach
            </select>
            <select name="year" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                <option value="">All Years</option>
                @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)<option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>@endfor
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-sm font-medium transition-colors">Filter</button>
            <a href="{{ route('uploads.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm">Reset</a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">File</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Measurement</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Period</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Status</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">AI Result</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Uploaded</th>
                    <th class="text-right px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($uploads as $upload)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                @if(str_contains($upload->file_type, 'pdf'))
                                <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/></svg>
                                @elseif(str_contains($upload->file_type, 'image'))
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @else
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 dark:text-white truncate max-w-[200px]">{{ $upload->file_name }}</p>
                                <p class="text-xs text-slate-500">{{ number_format($upload->file_size / 1024, 1) }} KB</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-700 dark:text-slate-300 text-sm">{{ $upload->measurement->measurement ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-center"><span class="px-2.5 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-medium">{{ $upload->quarter }} {{ $upload->year }}</span></td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                            {{ $upload->status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                            {{ $upload->status === 'processing' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                            {{ $upload->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                            {{ $upload->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : '' }}">
                            {{ ucfirst($upload->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($upload->aiResult)
                            @if($upload->aiResult->evidence_valid)
                            <span class="text-emerald-600 dark:text-emerald-400 text-xs font-medium">Valid ({{ $upload->aiResult->confidence }}%)</span>
                            @else
                            <span class="text-rose-600 dark:text-rose-400 text-xs font-medium">Invalid</span>
                            @endif
                        @else
                        <span class="text-slate-400 text-xs">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500">
                        <p>{{ $upload->user->name ?? 'N/A' }}</p>
                        <p class="text-xs">{{ $upload->created_at->diffForHumans() }}</p>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('uploads.show', $upload) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 transition-colors" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                            <form method="POST" action="{{ route('uploads.destroy', $upload) }}" onsubmit="return confirm('Delete this evidence?')">@csrf @method('DELETE')<button class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">No evidence uploaded yet. <a href="{{ route('uploads.create') }}" class="text-indigo-600 hover:underline">Upload your first evidence</a></td></tr>
                @endforelse
            </tbody>
        </table>
        @if($uploads->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">{{ $uploads->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
