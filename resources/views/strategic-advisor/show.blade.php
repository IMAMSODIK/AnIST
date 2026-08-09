@extends('layouts.app')
@section('title', 'Strategic Advisor Detail')
@section('page-title', 'Strategic Advisor Detail')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Back link --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('strategic-advisor.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <form method="POST" action="{{ route('strategic-advisor.destroy', $recommendation) }}" onsubmit="return confirm('Hapus catatan ini? File upload juga akan dihapus.');">
            @csrf @method('DELETE')
            <button class="text-sm text-rose-600 dark:text-rose-400 hover:underline inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus
            </button>
        </form>
    </div>

    {{-- Header Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start justify-between mb-5 gap-4">
            <div class="min-w-0">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white truncate">{{ $recommendation->source_file }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    {{ $recommendation->document_type !== 'unknown' ? strtoupper($recommendation->document_type) : 'Tipe tidak dikenali' }}
                    @if($recommendation->company) &middot; {{ $recommendation->company }} @endif
                    @if($recommendation->period) &middot; {{ $recommendation->period }} @endif
                </p>
            </div>
            <span class="inline-flex flex-shrink-0 items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium
                {{ $recommendation->status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                {{ $recommendation->status === 'processing' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                {{ $recommendation->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : '' }}
                {{ $recommendation->status === 'pending' ? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' : '' }}">
                @if($recommendation->status === 'completed')
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Selesai
                @elseif($recommendation->status === 'processing')
                <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="3" class="opacity-75"/></svg>Memproses
                @elseif($recommendation->status === 'failed')
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Gagal
                @else Pending @endif
            </span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Halaman</p>
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $recommendation->total_pages ?: '—' }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Waktu Proses</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $recommendation->processing_time ? number_format((float) $recommendation->processing_time, 1) . 's' : '—' }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Dibuat</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $recommendation->created_at->format('d M Y') }}</p>
                <p class="text-xs text-slate-500">{{ $recommendation->created_at->format('H:i') }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 text-center">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Oleh</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ $recommendation->user->name ?? 'N/A' }}</p>
            </div>
        </div>

        @if($recommendation->status === 'completed')
        <div class="mt-4 flex items-center gap-2">
            @if($recommendation->grounded)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-medium border border-emerald-100 dark:border-emerald-800/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Live Internet Grounding: ON
                <span class="text-emerald-500/80 dark:text-emerald-400/80 normal-case font-normal">— tren dari Google Search terkini</span>
            </span>
            @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-xl text-xs font-medium border border-amber-100 dark:border-amber-800/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Live Internet Grounding: OFF
                <span class="text-amber-700/70 dark:text-amber-300/70 normal-case font-normal">— tren dari knowledge model (fallback free tier)</span>
            </span>
            @endif
        </div>
        @endif
    </div>

    {{-- Failed state --}}
    @if($recommendation->status === 'failed')
    <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/50 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-rose-700 dark:text-rose-300 mb-2 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Analisis Gagal
        </h3>
        <p class="text-sm text-rose-700 dark:text-rose-300">{{ $recommendation->error_message ?: 'Terjadi kesalahan tidak dikenal.' }}</p>
        @if($recommendation->extraction_json)
        <p class="text-xs text-rose-600/80 dark:text-rose-400/80 mt-3">Ekstraksi dokumen tetap berhasil — lihat ringkasan struktur di bawah.</p>
        @endif
    </div>
    @endif

    @php
        $recommendations = $recommendation->recommendations_array;
        $trends = $recommendation->popular_trends_array;
    @endphp

    {{-- AI Analysis --}}
    @if($recommendation->analysis)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            AI Analysis
        </h3>
        <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $recommendation->analysis }}</div>
    </div>
    @endif

    {{-- Perspective Coverage (dihapus sesuai permintaan) --}}

    {{-- Recommendations --}}
    @if(count($recommendations) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Strategic Recommendations ({{ count($recommendations) }})
        </h3>
        <div class="space-y-3">
            @foreach($recommendations as $i => $rec)
            @php
                $priority = strtolower($rec['priority'] ?? 'medium');
                $priorityColor = match($priority) {
                    'high' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                    'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                    default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                };
            @endphp
            <div class="border border-slate-100 dark:border-slate-700 rounded-xl p-4 hover:border-indigo-200 dark:hover:border-indigo-700 transition-colors">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-white">{{ $rec['title'] ?? 'Untitled' }}</h4>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if(! empty($rec['priority']))
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $priorityColor }}">{{ ucfirst($priority) }}</span>
                        @endif
                    </div>
                </div>
                @if(! empty($rec['rationale']))
                <p class="text-sm text-slate-600 dark:text-slate-400 ml-9 leading-relaxed">{{ $rec['rationale'] }}</p>
                @endif
                @if(! empty($rec['suggested_perspective']) || ! empty($rec['suggested_initiative']))
                <div class="mt-2 ml-9 flex flex-wrap gap-1.5">
                    @if(! empty($rec['suggested_perspective']))
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300 border border-violet-100 dark:border-violet-800/50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a1.99 1.99 0 010 2.828l-7 7a1.99 1.99 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/></svg>
                        {{ $rec['suggested_perspective'] }}
                    </span>
                    @endif
                    @if(! empty($rec['suggested_initiative']))
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300 border border-cyan-100 dark:border-cyan-800/50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ $rec['suggested_initiative'] }}
                    </span>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Popular Trends (Internet Grounding) --}}
    @if(count($trends) > 0)
    <div class="bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-slate-800 dark:to-slate-800 rounded-2xl border border-cyan-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Current Internet Trends
        </h3>
        @if($recommendation->grounded)
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Disarankan oleh Gemini berdasarkan grounding Google Search terhadap perkembangan terkini.</p>
        @else
        <p class="text-xs text-amber-700 dark:text-amber-300 mb-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 rounded-lg px-3 py-2 inline-block">
            ⚠ Mode fallback: live grounding tidak tersedia di API key ini, tren di bawah berasal dari <span class="font-medium">knowledge model</span> (training cutoff), bukan live web.
        </p>
        @endif
        <div class="grid md:grid-cols-2 gap-3">
            @foreach($trends as $t)
            <div class="bg-white/70 dark:bg-slate-700/30 backdrop-blur-sm rounded-xl p-4 border border-white/60 dark:border-slate-700">
                <p class="text-sm font-semibold text-slate-800 dark:text-white flex items-start gap-2">
                    <svg class="w-4 h-4 text-cyan-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    {{ $t['trend'] ?? 'Trend' }}
                </p>
                @if(! empty($t['relevance_to_document']))
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1.5 ml-6 leading-relaxed">{{ $t['relevance_to_document'] }}</p>
                @endif
                @if(! empty($t['source_hint']))
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 ml-6 italic">Sumber: {{ $t['source_hint'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Matched KPIs Table (dihapus sesuai permintaan) --}}

    {{-- Extraction summary (collapsible) --}}
    @if($recommendation->extraction_json)
    @php $extraction = $recommendation->extraction_json; @endphp
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6" x-data="{ open: false }">
        <button @click="open = !open" class="w-full flex items-center justify-between text-left">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Ringkasan Ekstraksi Dokumen
            </h3>
            <svg class="w-5 h-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-collapse class="mt-4 space-y-4">
            @if(! empty($extraction['executive_summary']))
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-1">Ringkasan Eksekutif</p>
                <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $extraction['executive_summary'] }}</div>
            </div>
            @endif
            @if(! empty($extraction['strategic_objectives']))
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-2">Sasaran Strategis ({{ count($extraction['strategic_objectives']) }})</p>
                <ul class="text-sm text-slate-700 dark:text-slate-300 space-y-1">
                    @foreach($extraction['strategic_objectives'] as $so)
                    <li>- <span class="font-mono text-xs">{{ $so['code'] ?? '' }}</span> {{ $so['name'] ?? '' }} @if(! empty($so['perspective']))<span class="text-xs text-slate-400">({{ $so['perspective'] }})</span>@endif</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(! empty($extraction['initiatives']))
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-2">Inisiatif Terdeteksi ({{ count($extraction['initiatives']) }})</p>
                <details class="text-sm text-slate-700 dark:text-slate-300">
                    <summary class="cursor-pointer text-indigo-600 dark:text-indigo-400 text-xs">Tampilkan semua</summary>
                    <ul class="mt-2 space-y-1 max-h-60 overflow-y-auto">
                        @foreach($extraction['initiatives'] as $init)
                        <li class="text-xs">- <span class="font-mono">{{ $init['code'] ?? '' }}</span> {{ $init['name'] ?? '' }}</li>
                        @endforeach
                    </ul>
                </details>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Raw JSON (collapsible) --}}
    @if($recommendation->raw_response_json)
    <div class="bg-slate-900 dark:bg-slate-900 rounded-2xl border border-slate-700 p-6" x-data="{ open: false }">
        <button @click="open = !open" class="w-full flex items-center justify-between text-left">
            <h3 class="text-sm font-semibold text-slate-300 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                Raw Gemini Response (debug)
            </h3>
            <svg class="w-5 h-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-collapse class="mt-3">
            <pre class="text-xs text-slate-300 font-mono overflow-x-auto max-h-96 overflow-y-auto">{{ json_encode($recommendation->raw_response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
    @endif

</div>
@endsection