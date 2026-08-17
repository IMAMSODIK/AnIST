@extends('layouts.app')
@section('title', 'Strategic Advisor Riwayat')
@section('page-title', 'Strategic Advisor — Riwayat')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">Riwayat Tanya Jawab</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Semua pertanyaan &amp; jawaban Strategic Advisor berdasarkan dokumen di knowledge base.</p>
        </div>
        <a href="{{ route('strategic-advisor.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors inline-flex items-center gap-2 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Tanya Lagi
        </a>
    </div>

    {{-- Filter --}}
    <form method="GET" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 flex flex-wrap items-center gap-3">
        <label class="text-sm text-slate-600 dark:text-slate-400">Filter status:</label>
        <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm">
            <option value="">Semua</option>
            @foreach(['processing','completed','failed'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="px-3 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg text-sm">Terapkan</button>
        @if(request('status'))
        <a href="{{ route('strategic-advisor.history') }}" class="text-xs text-slate-500 hover:text-indigo-600">Reset</a>
        @endif
    </form>

    {{-- List --}}
    @if($records->isEmpty())
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-10 text-center text-sm text-slate-400 dark:text-slate-500">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Belum ada pertanyaan.
    </div>
    @else
    <div class="space-y-3">
        @foreach($records as $r)
        <a href="{{ route('strategic-advisor.show', $r) }}" class="block bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 p-5 transition-colors group">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-800 dark:text-white line-clamp-2 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{ $r->question }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 line-clamp-2">{{ Str::limit($r->answer ?: $r->error_message, 220) }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 flex flex-wrap items-center gap-1.5">
                        {{ $r->created_at->format('d M Y H:i') }} &middot; oleh {{ $r->user->name ?? 'N/A' }}
                        @if($r->processing_time) &middot; {{ number_format((float) $r->processing_time, 1) }}s @endif
                        @if(count($r->citations_array) > 0)
                        &middot; <span class="px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300">{{ count($r->citations_array) }} sitasi</span>
                        @endif
                        @if($r->status === 'completed' && $r->grounded)
                        &middot; <span class="px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300">live web</span>
                        @endif
                    </p>
                </div>
                <span class="inline-flex flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-medium
                    {{ $r->status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                    {{ $r->status === 'processing' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                    {{ $r->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : '' }}">
                    @if($r->status === 'completed')Selesai
                    @elseif($r->status === 'processing')Memproses
                    @else Gagal @endif
                </span>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="pt-2">{{ $records->links() }}</div>
    @endif
</div>
@endsection
