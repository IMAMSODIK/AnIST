@extends('layouts.chat')
@section('title', 'Strategic Advisor')

@section('content')
<style>
/* Styling hasil render markdown jawaban Gemini — pengganti prose/typography
   plugin yang tidak terpasang di Tailwind v4 project ini. */
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

/* ==== Typing indicator saat menunggu jawaban Gemini ==== */
.typing-dots { display: inline-flex; gap: 4px; align-items: center; }
.typing-dots span {
    width: 6px; height: 6px; border-radius: 9999px;
    background: currentColor;
    animation: typing-bounce 1.2s infinite ease-in-out;
}
.typing-dots span:nth-child(2) { animation-delay: .15s; }
.typing-dots span:nth-child(3) { animation-delay: .3s; }
@keyframes typing-bounce {
    0%, 60%, 100% { transform: translateY(0); opacity: .35; }
    30%           { transform: translateY(-4px); opacity: 1; }
}
[x-cloak] { display: none !important; }
/* Scrollbar tipis untuk daftar sesi & chat */
.thin-scroll::-webkit-scrollbar { width: 6px; }
.thin-scroll::-webkit-scrollbar-thumb { background: rgba(100,116,139,.35); border-radius: 9999px; }
.thin-scroll::-webkit-scrollbar-track { background: transparent; }
</style>

<div class="flex h-screen overflow-hidden" x-data="advisorChat()" x-cloak>

    {{-- ================= LEFT: Sidebar sesi (ala ChatGPT) ================= --}}
    <aside class="w-72 flex-shrink-0 bg-slate-50 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col"
           :class="sidebarOpen ? '' : 'hidden md:flex'">

        {{-- Brand + new chat --}}
        <div class="p-3 border-b border-slate-200 dark:border-slate-800 space-y-3">
            <div class="flex items-center gap-2 px-1 pt-1">
                <img src="{{ asset('logo/anist.png') }}" alt="AnIST" class="w-8 h-8 rounded-lg object-contain">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-800 dark:text-white leading-tight">Strategic Advisor</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 leading-tight">AI Assistant AnIST</p>
                </div>
                <button type="button" @click="sidebarOpen = false" class="md:hidden ml-auto text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <button type="button" @click="newChat()"
                    class="w-full px-3 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Sesi Baru
            </button>
        </div>

        {{-- Daftar sesi --}}
        <div class="flex-1 overflow-y-auto thin-scroll px-3 py-3 space-y-1">
            <p class="px-2 pb-1 text-[10px] font-bold tracking-widest text-slate-400 dark:text-slate-500 uppercase">Riwayat Chat</p>

            <div x-show="sessions.length === 0" class="px-2 py-6 text-xs text-slate-400 dark:text-slate-500 text-center">
                Belum ada percakapan.<br>Mulai sesi baru untuk bertanya.
            </div>

            <template x-for="s in sessions" :key="s.id">
                <div class="group relative">
                    <button type="button" @click="openSession(s.id)"
                            class="w-full text-left px-3 py-2.5 rounded-xl text-sm transition-colors pr-9"
                            :class="activeSessionId === s.id
                                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            <span class="truncate" x-text="s.title" :title="s.title"></span>
                        </span>
                        <span class="block text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 pl-6" x-text="s.message_count + ' pesan · ' + s.last_activity"></span>
                    </button>
                    <button type="button" @click="deleteSession(s.id)"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-lg text-slate-300 hover:text-rose-500 dark:text-slate-600 dark:hover:text-rose-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            :title="'Hapus sesi'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- Footer sidebar --}}
        <div class="p-3 border-t border-slate-200 dark:border-slate-800 space-y-1">
            <a href="{{ route('strategic-advisor.documents.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Knowledge Base
                <span class="ml-auto px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300" x-text="documentsCount + ' dok'"></span>
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Aplikasi
            </a>
            @endif
            @if(! auth()->user()->isAdmin())
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </form>
            @endif
            <div class="flex items-center gap-2.5 px-3 py-2 pt-3 mt-1 border-t border-slate-200 dark:border-slate-800">
                <div class="w-7 h-7 rounded-full bg-indigo-600/10 dark:bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                    <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- ================= RIGHT: Area chat ================= --}}
    <div class="flex-1 flex flex-col min-w-0 relative">

        {{-- Topbar chat --}}
        <div class="h-14 border-b border-slate-200 dark:border-slate-800 flex items-center gap-3 px-4 flex-shrink-0">
            <button type="button" @click="sidebarOpen = true" class="md:hidden text-slate-500 hover:text-slate-700 dark:hover:text-slate-200" x-show="!sidebarOpen">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate" x-text="activeTitle()"></h1>
            <div class="ml-auto flex items-center gap-2">
                <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    {{ $documentsCount }} dokumen sumber
                </span>
                <button type="button" @click="newChat()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Sesi Baru
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto thin-scroll" x-ref="chatBox">
            <div class="max-w-3xl mx-auto px-4 py-6 space-y-8">

                {{-- Empty state --}}
                <div x-show="messages.length === 0 && !loadingSession" class="py-16 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center mx-auto mb-5 shadow-lg shadow-indigo-500/25">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 dark:text-white">Ada yang bisa saya bantu?</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto leading-relaxed">
                        Tanyakan apa saja tentang dokumen di knowledge base. Jawaban dilengkapi
                        <span class="font-medium">sitasi dokumen &amp; halaman</span> serta tren internet terkini.
                    </p>

                    {{-- Suggestion chips --}}
                    <div class="mt-6 grid sm:grid-cols-2 gap-2.5 max-w-xl mx-auto text-left">
                        <button type="button" @click="useSuggestion('Bagaimana keselarasan KPI di RJPP dengan inisiatif PTI di MPTI?')"
                                class="group p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">Analisis Keselarasan</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 leading-relaxed">Kesesuaian KPI RJPP dengan inisiatif PTI di MPTI</p>
                        </button>
                        <button type="button" @click="useSuggestion('Beri saran prioritas transformasi digital 2026 beserta tren terkini.')"
                                class="group p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">Saran Strategis</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 leading-relaxed">Prioritas transformasi digital + tren terkini</p>
                        </button>
                        <button type="button" @click="useSuggestion('Rangkum poin strategis utama dari seluruh dokumen di knowledge base.')"
                                class="group p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">Ringkasan Dokumen</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 leading-relaxed">Poin strategis utama lintas dokumen</p>
                        </button>
                        <button type="button" @click="useSuggestion('Identifikasi risiko utama dalam perencanaan strategis berdasarkan dokumen yang tersedia.')"
                                class="group p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">Analisis Risiko</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 leading-relaxed">Risiko perencanaan strategis dari dokumen</p>
                        </button>
                    </div>
                </div>

                {{-- Loading sesi --}}
                <div x-show="loadingSession" class="py-16 flex items-center justify-center gap-3 text-sm text-slate-400">
                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="3" class="opacity-75"/></svg>
                    Memuat percakapan...
                </div>

                {{-- Daftar pesan --}}
                <template x-for="m in messages" :key="m.id">
                    <div class="space-y-5">
                        {{-- User bubble --}}
                        <div class="flex justify-end">
                            <div class="max-w-[80%] bg-indigo-600 text-white rounded-2xl rounded-br-md px-4 py-3 text-sm leading-relaxed whitespace-pre-line" x-text="m.question"></div>
                        </div>

                        {{-- Assistant answer --}}
                        <div class="flex justify-start">
                            <div class="max-w-[95%] w-full space-y-4">

                                {{-- Pending --}}
                                <div x-show="m.pending" class="flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                                    <span class="typing-dots text-indigo-500 flex-shrink-0"><span></span><span></span><span></span></span>
                                    <span>Mencari halaman relevan &amp; menanyakan Gemini (15-60 detik)...</span>
                                </div>

                                {{-- Failed --}}
                                <div x-show="!m.pending && m.status === 'failed'" class="text-sm text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-900/40 rounded-xl px-4 py-3">
                                    <p class="font-medium mb-1">Gagal menjawab:</p>
                                    <p x-text="m.error_message || 'Terjadi kesalahan tidak dikenal.'"></p>
                                </div>

                                {{-- Completed --}}
                                <div x-show="!m.pending && m.status === 'completed'" class="space-y-4">
                                    <div class="flex gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                        </div>
                                        <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed answer-md flex-1 min-w-0"
                                             x-html="window.mdToHtml ? mdToHtml(m.answer || '') : (m.answer || '')"></div>
                                    </div>

                                    {{-- Citations --}}
                                    <div x-show="m.citations && m.citations.length > 0" class="pl-10">
                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-2 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Sumber (dokumen &amp; halaman)
                                        </p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="(c, ci) in m.citations" :key="ci">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-slate-50 dark:bg-slate-900 border border-indigo-100 dark:border-indigo-800/50 text-indigo-700 dark:text-indigo-300 cursor-help"
                                                      :title="c.quote || ''">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    <span class="truncate max-w-[240px]" x-text="(c.document || 'Dokumen') + ' — hal. ' + c.page"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Recommendations --}}
                                    <div x-show="m.recommendations && m.recommendations.length > 0" class="pl-10 border-t border-slate-100 dark:border-slate-800 pt-3">
                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-2 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Saran Strategis
                                        </p>
                                        <ol class="space-y-2">
                                            <template x-for="(r, ri) in m.recommendations" :key="ri">
                                                <li class="text-sm text-slate-700 dark:text-slate-300">
                                                    <span class="font-medium" x-html="window.mdToHtml ? mdToHtmlInline((ri + 1) + '. ' + (r.title || '')) : ((ri + 1) + '. ' + (r.title || ''))"></span>
                                                    <span x-show="r.detail" class="block text-slate-600 dark:text-slate-400 text-xs leading-relaxed answer-md mt-0.5" x-html="window.mdToHtml ? mdToHtml(r.detail) : (r.detail || '')"></span>
                                                </li>
                                            </template>
                                        </ol>
                                    </div>

                                    {{-- Trends --}}
                                    <div x-show="m.trends && m.trends.length > 0" class="pl-10 border-t border-slate-100 dark:border-slate-800 pt-3">
                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-2 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Tren Internet Terkini
                                        </p>
                                        <div class="space-y-2.5">
                                            <template x-for="(t, ti) in m.trends" :key="ti">
                                                <div class="text-sm">
                                                    <span class="font-medium text-slate-700 dark:text-slate-200" x-html="window.mdToHtml ? mdToHtmlInline(t.trend) : (t.trend || '')"></span>
                                                    <span x-show="t.relevance" class="block text-xs text-slate-500 dark:text-slate-400 leading-relaxed answer-md mt-0.5" x-html="window.mdToHtml ? mdToHtml(t.relevance) : (t.relevance || '')"></span>
                                                    <span x-show="t.source" class="block text-[11px] text-slate-400 italic mt-0.5" x-text="'Sumber: ' + t.source"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <p class="pl-10 text-[11px] text-slate-400 dark:text-slate-500">
                                        <span x-text="m.processing_time ? m.processing_time + 's · ' : ''"></span>
                                        <span x-text="m.created_at"></span>
                                        <span x-show="m.grounded"> · grounded web</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="h-4"></div>
            </div>
        </div>

        {{-- Input box --}}
        <div class="flex-shrink-0 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950">
            <div class="max-w-3xl mx-auto px-4 py-4">
                <div class="flex items-end gap-3">
                    <textarea x-ref="questionInput"
                              rows="1"
                              class="flex-1 resize-none border border-slate-200 dark:border-slate-700 dark:bg-slate-900 rounded-2xl px-4 py-4 leading-5 text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                              style="height: 52px; min-height: 52px; max-height: 200px;"
                              placeholder="Tulis pertanyaan atau minta saran strategis... (Enter kirim, Shift+Enter baris baru)"
                              :disabled="asking || loadingSession"
                              x-model="question"
                              @keydown.enter.prevent="sendQuestion()"
                              @input="autoGrow()"></textarea>
                    <button type="button" @click="sendQuestion()"
                            :disabled="asking || question.trim().length < 5 || loadingSession"
                            :style="'height:' + inputHeight + 'px'"
                            class="px-6 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-200 dark:disabled:bg-slate-700 disabled:cursor-not-allowed text-white rounded-xl text-sm font-medium transition-colors inline-flex items-center gap-2 flex-shrink-0">
                        <svg x-show="!asking" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <svg x-show="asking" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="3" class="opacity-75"/></svg>
                        <span x-text="asking ? 'Memproses' : 'Kirim'"></span>
                    </button>
                </div>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 text-center">
                    Jawaban bersumber dari knowledge base dokumen + tren internet terkini (Google Search grounding).
                    <span x-show="documentsCount === 0" class="text-amber-500">Belum ada dokumen — <a href="{{ route('strategic-advisor.documents.index') }}" class="underline hover:text-indigo-500">unggah dokumen dulu</a>.</span>
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function advisorChat() {
    return {
        sidebarOpen: window.innerWidth >= 768,
        asking: false,
        loadingSession: false,
        question: '',
        inputHeight: 52,

        documentsCount: {{ (int) $documentsCount }},
        activeSessionId: {{ $activeSessionId ? (int) $activeSessionId : 'null' }},

        sessions: @json($sessionsJson),
        messages: @json($messagesJson),

        activeTitle() {
            if (! this.activeSessionId) return 'Percakapan Baru';
            const s = this.sessions.find(x => x.id === this.activeSessionId);
            return s ? s.title : 'Percakapan';
        },

        // ---------- sesi ----------
        newChat() {
            this.activeSessionId = null;
            this.messages = [];
            this.question = '';
            this.$nextTick(() => this.scrollToBottom());
            if (window.innerWidth < 768) this.sidebarOpen = false;
        },
        async openSession(id) {
            if (this.asking || id === this.activeSessionId) {
                if (window.innerWidth < 768) this.sidebarOpen = false;
                return;
            }
            this.loadingSession = true;
            this.activeSessionId = id;
            this.messages = [];
            try {
                const resp = await fetch('{!! route('strategic-advisor.sessions.show', ':ID') !!}'.replace(':ID', id), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.messages) {
                    this.messages = data.messages;
                    if (data.session) this.upsertSession(data.session);
                } else {
                    alert('Gagal memuat sesi (HTTP ' + resp.status + ').');
                }
            } catch (err) {
                alert('Gagal memuat sesi: ' + (err.message || err));
            }
            this.loadingSession = false;
            this.$nextTick(() => this.scrollToBottom());
            if (window.innerWidth < 768) this.sidebarOpen = false;
            // Sinkronkan URL tanpa reload agar sesi bisa di-bookmark.
            const url = '{!! route('strategic-advisor.index') !!}' + '?session=' + id;
            window.history.replaceState({}, '', url);
        },
        async deleteSession(id) {
            const s = this.sessions.find(x => x.id === id);
            if (! confirm('Hapus percakapan "' + (s ? s.title : '') + '"? Seluruh pesan di sesi ini akan dihapus.')) return;
            try {
                const resp = await fetch('{!! route('strategic-advisor.sessions.destroy', ':ID') !!}'.replace(':ID', id), {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (resp.ok) {
                    this.sessions = this.sessions.filter(x => x.id !== id);
                    if (this.activeSessionId === id) {
                        this.activeSessionId = null;
                        this.messages = [];
                    }
                } else {
                    alert('Gagal menghapus sesi (HTTP ' + resp.status + ').');
                }
            } catch (err) {
                alert('Gagal menghapus sesi: ' + (err.message || err));
            }
        },
        upsertSession(session) {
            if (! session) return;
            this.sessions = this.sessions.filter(x => x.id !== session.id);
            this.sessions.unshift(session);
        },

        // ---------- ask ----------
        useSuggestion(text) {
            this.question = text;
            this.$refs.questionInput?.focus();
        },
        autoGrow() {
            const el = this.$refs.questionInput;
            if (! el) return;
            el.style.height = '52px';
            const h = Math.min(el.scrollHeight, 200);
            el.style.height = h + 'px';
            this.inputHeight = h;
        },
        async sendQuestion() {
            const q = this.question.trim();
            if (q.length < 5 || this.asking || this.loadingSession) return;
            this.asking = true;
            this.question = '';
            this.$nextTick(() => this.autoGrow());

            // Sesi baru dibuat implisit pada pertanyaan pertama.
            if (! this.activeSessionId) {
                try {
                    const resp = await fetch('{!! route('strategic-advisor.sessions.store') !!}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await resp.json().catch(() => ({}));
                    if (! (resp.ok && data.session)) {
                        alert('Gagal membuat sesi baru: ' + (data.message || 'HTTP ' + resp.status));
                        this.asking = false;
                        return;
                    }
                    this.activeSessionId = data.session.id;
                    this.upsertSession(data.session);
                } catch (err) {
                    alert('Gagal membuat sesi: ' + (err.message || err));
                    this.asking = false;
                    return;
                }
            }

            const optimistic = {
                id: 'tmp-' + Date.now(),
                question: q,
                answer: null,
                citations: [],
                trends: [],
                recommendations: [],
                grounded: false,
                status: 'pending',
                error_message: null,
                processing_time: null,
                created_at: 'baru saja',
                pending: true,
            };
            this.messages.push(optimistic);
            // PENTING reaktivitas Alpine: akses ulang item via array agar
            // mendapatkan referensi ter-proxy. Memutasi objek mentah TIDAK
            // memicu re-render.
            const msg = this.messages[this.messages.length - 1];
            this.$nextTick(() => this.scrollToBottom());

            try {
                const fd = new FormData();
                fd.append('question', q);
                fd.append('session_id', this.activeSessionId);
                fd.append('_token', this.csrfToken());

                const resp = await fetch('{!! route('strategic-advisor.ask') !!}', {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json' },
                });
                const data = await resp.json().catch(() => ({}));

                const m = data.message || {};
                Object.assign(msg, {
                    id: m.id || msg.id,
                    answer: m.answer || null,
                    citations: m.citations || [],
                    trends: m.trends || [],
                    recommendations: m.recommendations || [],
                    grounded: !!m.grounded,
                    status: (resp.ok && data.status === 'completed') ? 'completed' : 'failed',
                    error_message: data.error_message || data.message || m.error_message || 'Gagal tak terduga.',
                    processing_time: m.processing_time || null,
                    created_at: m.created_at || 'baru saja',
                    pending: false,
                });

                // Perbarui judul/count sesi di sidebar.
                if (data.session) this.upsertSession(data.session);
            } catch (err) {
                Object.assign(msg, {
                    pending: false,
                    status: 'failed',
                    error_message: (err && err.message) || 'Network gagal — cek koneksi.',
                });
            }

            this.asking = false;
            this.$nextTick(() => this.scrollToBottom());
        },
        scrollToBottom() {
            const el = this.$refs.chatBox;
            if (el) el.scrollTop = el.scrollHeight;
        },

        // ---------- utils ----------
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content;
        },
    };
}
</script>
@endpush
@endsection
