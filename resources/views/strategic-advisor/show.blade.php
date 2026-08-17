@extends('layouts.app')
@section('title', 'Strategic Advisor — Detail Jawaban')
@section('page-title', 'Strategic Advisor Detail')

@section('content')
<style>
/* Styling hasil render markdown jawaban Gemini (halaman detail) */
.answer-md p { margin: 0 0 .6rem; }
.answer-md p:last-child { margin-bottom: 0; }
.answer-md strong { font-weight: 600; color: inherit; }
.answer-md em { font-style: italic; }
.answer-md ul, .answer-md ol { margin: .25rem 0 .6rem; padding-left: 1.25rem; }
.answer-md ul { list-style: disc; }
.answer-md ol { list-style: decimal; }
.answer-md li { margin: .15rem 0; }
.answer-md h1, .answer-md h2, .answer-md h3, .answer-md h4 {
    font-weight: 600; margin: .75rem 0 .35rem; line-height: 1.35;
}
.answer-md h1 { font-size: 1.05rem; } .answer-md h2 { font-size: 1rem; }
.answer-md h3, .answer-md h4 { font-size: .95rem; }
.answer-md a { color: #4f46e5; text-decoration: underline; }
.answer-md blockquote {
    border-left: 3px solid #c7d2fe; padding-left: .75rem; margin: .5rem 0;
    color: #64748b; font-style: italic;
}
.answer-md code { background: rgba(0,0,0,.06); border-radius: 4px; padding: .1rem .3rem; font-size: .85em; }
@media (prefers-color-scheme: dark) {
    .answer-md code { background: rgba(255,255,255,.1); }
}
</style>
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Back link --}}
    <a href="{{ route('strategic-advisor.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>

    {{-- Question card --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-600/10 dark:bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-slate-400 dark:text-slate-500 mb-1">Pertanyaan &middot; {{ $message->created_at->format('d M Y H:i') }} &middot; oleh {{ $message->user->name ?? 'N/A' }}</p>
                <p class="text-base font-medium text-slate-800 dark:text-white leading-relaxed whitespace-pre-line">{{ $message->question }}</p>
            </div>
            <span class="inline-flex flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-medium
                {{ $message->status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                {{ $message->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : '' }}
                {{ $message->status === 'processing' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}">
                @if($message->status === 'completed')Selesai@elseif($message->status === 'failed')Gagal@else Memproses @endif
            </span>
        </div>

        @if($message->status === 'completed')
        <div class="flex flex-wrap items-center gap-2 mt-4">
            @if($message->grounded)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-medium border border-emerald-100 dark:border-emerald-800/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Live Internet Grounding: ON — tren dari Google Search terkini
            </span>
            @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-xl text-xs font-medium border border-amber-100 dark:border-amber-800/50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Live Internet Grounding: OFF — tren dari knowledge model
            </span>
            @endif
            @if($message->processing_time)
            <span class="px-3 py-1.5 bg-slate-50 dark:bg-slate-700/30 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-100 dark:border-slate-700">
                {{ number_format((float) $message->processing_time, 1) }}s
            </span>
            @endif
        </div>
        @endif
    </div>

    {{-- Failed state --}}
    @if($message->status === 'failed')
    <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/50 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-rose-700 dark:text-rose-300 mb-2">Gagal Menjawab</h3>
        <p class="text-sm text-rose-700 dark:text-rose-300">{{ $message->error_message ?: 'Terjadi kesalahan tidak dikenal.' }}</p>
    </div>
    @endif

    @php
        $citations = $message->citations_array;
        $trends = $message->trends_array;
        $recommendations = $message->recommendations_array;
        $contextDocs = $message->context_documents_json ?? [];
    @endphp

    {{-- Answer --}}
    @if($message->answer)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            Jawaban AI
        </h3>
        {{-- Render markdown server-side (Str::markdown = league/commonmark,
             default Laravel: html_input strip) sehingga **tebal**/*miring*
             tampil semestinya, bukan bintang-bintang mentah. --}}
        <div class="answer-md text-sm text-slate-700 dark:text-slate-300 leading-relaxed">{!! Str::markdown($message->answer ?? '') !!}</div>
    </div>
    @endif

    {{-- Citations --}}
    @if(count($citations) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Sitasi Dokumen ({{ count($citations) }})
        </h3>
        <div class="space-y-3">
            @foreach($citations as $c)
            <div class="border border-slate-100 dark:border-slate-700 rounded-xl p-4">
                <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                    {{ $c['document'] ?? 'Dokumen' }} &mdash; halaman {{ $c['page'] ?? '?' }}
                </p>
                @if(! empty($c['quote']))
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1.5 italic leading-relaxed">"{{ $c['quote'] }}"</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recommendations --}}
    @if(count($recommendations) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Saran Strategis ({{ count($recommendations) }})
        </h3>
        <div class="space-y-3">
            @foreach($recommendations as $i => $r)
            <div class="border border-slate-100 dark:border-slate-700 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                    <div class="min-w-0">
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-white">{{ $r['title'] ?? 'Untitled' }}</h4>
                        @if(! empty($r['detail']))
                        <div class="answer-md text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">{!! Str::markdown($r['detail']) !!}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Trends --}}
    @if(count($trends) > 0)
    <div class="bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-slate-800 dark:to-slate-800 rounded-2xl border border-cyan-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Tren Internet Terkini
        </h3>
        @if($message->grounded)
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Berdasarkan grounding Google Search terhadap perkembangan terkini.</p>
        @else
        <p class="text-xs text-amber-700 dark:text-amber-300 mb-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 rounded-lg px-3 py-2 inline-block">
            Mode fallback: tren berasal dari <span class="font-medium">knowledge model</span> (training cutoff), bukan live web.
        </p>
        @endif
        <div class="grid md:grid-cols-2 gap-3">
            @foreach($trends as $t)
            <div class="bg-white/70 dark:bg-slate-700/30 backdrop-blur-sm rounded-xl p-4 border border-white/60 dark:border-slate-700">
                <p class="text-sm font-semibold text-slate-800 dark:text-white flex items-start gap-2">
                    <svg class="w-4 h-4 text-cyan-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="answer-md">{!! Str::inlineMarkdown($t['trend'] ?? '') !!}</span>
                </p>
                @if(! empty($t['relevance']))
                <div class="answer-md text-xs text-slate-600 dark:text-slate-400 mt-1.5 ml-6 leading-relaxed">{!! Str::markdown($t['relevance']) !!}</div>
                @endif
                @if(! empty($t['source']))
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 ml-6 italic">Sumber: {{ $t['source'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Context snapshot (audit) --}}
    @if(count($contextDocs) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-sm font-semibold text-slate-600 dark:text-slate-300 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Konteks yang dikirim ({{ count($contextDocs) }} halaman)
        </h3>
        <div class="flex flex-wrap gap-1.5">
            @foreach($contextDocs as $ctx)
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs bg-slate-50 dark:bg-slate-700/40 border border-slate-100 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                {{ $ctx['document'] ?? '' }} — hal. {{ $ctx['page'] ?? '?' }}
            </span>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
