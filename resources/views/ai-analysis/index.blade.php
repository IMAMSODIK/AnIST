@extends('layouts.app')
@section('title', 'AI Analysis')
@section('page-title', 'AI Analysis')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">AI Analysis Results</h2>
            <p class="text-slate-500 text-sm mt-1">View all AI-powered evidence analysis results from Google Gemini</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="evidence_valid" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
                <option value="">All Results</option>
                <option value="1" {{ request('evidence_valid') === '1' ? 'selected' : '' }}>Valid Evidence</option>
                <option value="0" {{ request('evidence_valid') === '0' ? 'selected' : '' }}>Invalid Evidence</option>
            </select>
            <input type="number" name="confidence_min" value="{{ request('confidence_min') }}" placeholder="Min Confidence %" min="0" max="100" class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm w-44">
            <button type="submit" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-sm font-medium transition-colors">Filter</button>
            <a href="{{ route('ai-analysis.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-sm">Reset</a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Measurement</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">File</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Valid</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Realisasi</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Confidence</th>
                    <th class="text-center px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Processing</th>
                    <th class="text-left px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Date</th>
                    <th class="text-right px-6 py-3 font-medium text-slate-600 dark:text-slate-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($analyses as $result)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">{{ $result->upload->measurement->measurement ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 text-sm">
                        <span class="truncate max-w-[180px] inline-block">{{ $result->upload->file_name ?? 'N/A' }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($result->evidence_valid)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full text-xs font-medium">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Valid
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-full text-xs font-medium">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Invalid
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center font-semibold text-indigo-600 dark:text-indigo-400">{{ $result->realisasi ?? '-' }}</td>
                    <td class="px-6 py-4 text-center">
                        @php $conf = $result->confidence ?? 0; @endphp
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $conf >= 80 ? 'bg-emerald-500' : ($conf >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ min($conf, 100) }}%"></div>
                            </div>
                            <span class="text-xs text-slate-600 dark:text-slate-400">{{ $conf }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-slate-500">{{ $result->processing_time ?? 0 }}s</td>
                    <td class="px-6 py-4 text-sm text-slate-500">{{ $result->created_at->format('d M Y H:i') }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('ai-analysis.show', $result) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 transition-colors inline-block" title="View Details">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-12 text-center text-slate-500">No AI analysis results yet. <a href="{{ route('uploads.create') }}" class="text-indigo-600 hover:underline">Upload evidence</a> to start.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($analyses->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">{{ $analyses->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
