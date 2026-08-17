@extends('layouts.app')
@section('title', 'Strategic Advisor')
@section('page-title', 'Strategic Advisor')

@section('content')
<style>
/* Styling hasil render markdown jawaban Gemini (bubble chat) — pengganti
   prose/typography plugin yang tidak terpasang di Tailwind v4 project ini. */
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

/* ==== Animasi progres upload dokumen ==== */
@keyframes progress-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.progress-bar-anim {
    background-size: 200% 100%;
    animation: progress-shimmer 1.4s linear infinite;
    transition: width .25s ease-out;
}
@keyframes fade-slide-up {
    from { opacity: 0; transform: translateY(4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-slide-up { animation: fade-slide-up .3s ease-out both; }
@keyframes pop-check {
    0%   { transform: scale(.4); opacity: 0; }
    70%  { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}
.pop-check { animation: pop-check .35s ease-out both; }

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
</style>
<div class="max-w-7xl mx-auto space-y-6" x-data="advisorPage()" x-cloak>

    {{-- Hero --}}
    <div class="glass rounded-2xl border border-white/40 dark:border-slate-700/40 p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-600/10 dark:bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">AI Strategic Advisor</h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                    Unggah beberapa dokumen strategis (<span class="font-medium">RJPP</span> / <span class="font-medium">MPTI</span> / <span class="font-medium">paper</span>, PDF maks 50MB per file).
                    Sistem mengekstrak &amp; menyimpan isi dokumen <span class="font-medium">per halaman</span> ke knowledge base.
                    Lalu ajukan pertanyaan / minta saran — Gemini menjawab <span class="font-medium">dengan sitasi dokumen &amp; halaman</span>
                    serta memperhatikan tren internet terkini (Google Search grounding).
                </p>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-6 items-start">

        {{-- ================= LEFT: Knowledge Base ================= --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Upload Card --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Tambah Dokumen
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Hanya PDF, maksimal 50MB per file, boleh beberapa file sekaligus. Ekstraksi ~5-30 detik per dokumen (tanpa AI).</p>

                <form id="advisor-form" class="space-y-4">
                    @csrf
                    <div class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
                         :class="dragging ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-400 dark:hover:border-indigo-500'"
                         @click="$refs.fileInput.click()"
                         @dragover.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="handleDrop($event)">
                        <input type="file" x-ref="fileInput" class="hidden" accept=".pdf,application/pdf" multiple
                               @change="handlePick($event.target.files)">
                        <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Klik / drag &amp; drop PDF ke sini</p>
                        <p class="text-xs text-slate-400 mt-1">Bisa beberapa file sekaligus</p>
                    </div>

                    {{-- Upload queue --}}
                    <div x-show="uploadQueue.length > 0" x-transition class="space-y-2">
                        <template x-for="f in uploadQueue" :key="f.id">
                            <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl px-3 py-2 border border-slate-100 dark:border-slate-700 fade-slide-up">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                                              :class="{
                                                  'bg-slate-200 dark:bg-slate-600 text-slate-500': f.step === 'queueing',
                                                  'bg-amber-100 dark:bg-amber-900/40 text-amber-600': f.step === 'uploading',
                                                  'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600': f.step === 'completed',
                                                  'bg-rose-100 dark:bg-rose-900/40 text-rose-600': f.step === 'failed'
                                              }">
                                            <svg x-show="f.step === 'uploading'" class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="3" class="opacity-75"/></svg>
                                            <svg x-show="f.step === 'completed'" class="w-3 h-3 pop-check" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            <svg x-show="f.step === 'failed'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span x-show="f.step === 'queueing'" class="text-[10px] font-bold">•</span>
                                        </span>
                                        <span class="text-xs text-slate-700 dark:text-slate-200 truncate" x-text="f.file.name"></span>
                                    </div>
                                    <span class="text-xs flex-shrink-0"
                                          :class="f.step === 'uploading' ? 'font-semibold text-indigo-500' : 'text-slate-400'"
                                          x-text="f.step === 'uploading' ? Math.round(f.progress) + '%' : (f.error ? 'Gagal' : formatSize(f.file.size))"></span>
                                </div>

                                {{-- Progress bar + fase proses --}}
                                <div x-show="f.step === 'uploading' || f.step === 'completed'" x-transition class="mt-1.5">
                                    <div class="h-1.5 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-[width] duration-300 ease-out"
                                             :class="f.step === 'completed'
                                                 ? 'bg-gradient-to-r from-emerald-500 to-emerald-400'
                                                 : (f.step === 'failed'
                                                     ? 'bg-gradient-to-r from-rose-500 to-rose-400'
                                                     : 'bg-gradient-to-r from-indigo-500 via-violet-500 to-indigo-500 progress-bar-anim bg-[length:200%_100%]')"
                                             :style="'width:' + f.progress + '%'"></div>
                                    </div>
                                    <div class="flex justify-between mt-1" x-show="f.step === 'uploading'">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 truncate pr-2" x-text="f.phase"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p x-show="fError()" x-text="firstError()" class="text-xs text-rose-500"></p>
                    </div>

                    <button type="button" @click="startUpload()"
                            :disabled="uploadQueue.length === 0 || uploading"
                            class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 dark:disabled:bg-slate-600 disabled:cursor-not-allowed text-white rounded-xl text-sm font-medium transition-colors inline-flex items-center justify-center gap-2">
                        <svg x-show="!uploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <svg x-show="uploading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="3" class="opacity-75"/></svg>
                        <span x-text="uploading ? ('Memproses ' + uploadElapsed + 's') : 'Ekstrak & Simpan'"></span>
                    </button>
                </form>
            </div>

            {{-- Document Library --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Knowledge Base
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300" x-text="documents.length + ' dok'"></span>
                    </h3>
                </div>

                <div x-show="documents.length === 0" class="text-center py-8 text-sm text-slate-400 dark:text-slate-500">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Belum ada dokumen. Unggah dokumen untuk memulai.
                </div>

                <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                    <template x-for="d in documents" :key="d.id">
                        <div class="flex items-start justify-between gap-3 bg-slate-50 dark:bg-slate-700/30 rounded-xl p-3 border border-slate-100 dark:border-slate-700 group">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-800 dark:text-white truncate" x-text="d.name" :title="d.name"></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    <span x-text="d.total_pages + ' halaman'"></span>
                                    <span x-show="d.company"> &middot; <span x-text="d.company"></span></span>
                                    <span x-show="d.period"> &middot; <span x-text="d.period"></span></span>
                                </p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5" x-text="d.created_at"></p>
                                <p x-show="d.error_message" class="text-xs text-amber-600 dark:text-amber-400 mt-1" x-text="d.error_message"></p>
                            </div>
                            <button type="button" @click="deleteDocument(d)"
                                    class="text-slate-300 hover:text-rose-500 dark:text-slate-600 dark:hover:text-rose-400 flex-shrink-0 transition-colors"
                                    :disabled="uploading || asking">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                @if($documents->hasPages())
                <div class="pt-3">{{ $documents->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ================= RIGHT: Chat / Q&A ================= --}}
        <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col" style="min-height: 640px;">

            {{-- Chat header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Tanya Strategic Advisor
                </h3>
                <a href="{{ route('strategic-advisor.history') }}" class="text-xs text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Riwayat
                </a>
            </div>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6" x-ref="chatBox">
                <div x-show="messages.length === 0" class="text-center py-16">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Ajukan pertanyaan atau minta saran strategis</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-md mx-auto">
                        Contoh: "Bagaimana keselarasan KPI di RJPP dengan inisiatif PTI di MPTI?" atau
                        "Beri saran prioritas transformasi digital 2026 beserta tren terkini."
                    </p>
                </div>

                <template x-for="m in messages" :key="m.id">
                    <div class="space-y-3">
                        {{-- User bubble --}}
                        <div class="flex justify-end">
                            <div class="max-w-[85%] bg-indigo-600 text-white rounded-2xl rounded-br-md px-4 py-3 text-sm leading-relaxed whitespace-pre-line" x-text="m.question"></div>
                        </div>

                        {{-- Assistant answer --}}
                        <div class="flex justify-start">
                            <div class="max-w-[95%] w-full bg-slate-50 dark:bg-slate-700/30 rounded-2xl rounded-bl-md border border-slate-100 dark:border-slate-700 px-5 py-4">

                                {{-- Pending --}}
                                <div x-show="m.pending" class="flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                                    <span class="typing-dots text-indigo-500 flex-shrink-0"><span></span><span></span><span></span></span>
                                    <span>Mencari halaman relevan di <span x-text="documents.length"></span> dokumen &amp; menanyakan Gemini (15-60 detik)...</span>
                                </div>

                                {{-- Failed --}}
                                <div x-show="!m.pending && m.status === 'failed'" class="text-sm text-rose-600 dark:text-rose-400">
                                    <p class="font-medium mb-1">Gagal menjawab:</p>
                                    <p x-text="m.error_message || 'Terjadi kesalahan tidak dikenal.'"></p>
                                </div>

                                {{-- Completed --}}
                                <div x-show="!m.pending && m.status === 'completed'" class="space-y-4">
                                    <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed answer-md"
                                         x-html="window.mdToHtml ? mdToHtml(m.answer || '') : (m.answer || '')"></div>

                                    {{-- Citations --}}
                                    <div x-show="m.citations && m.citations.length > 0">
                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-2 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Sumber (dokumen &amp; halaman)
                                        </p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="(c, ci) in m.citations" :key="ci">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-white dark:bg-slate-800 border border-indigo-100 dark:border-indigo-800/50 text-indigo-700 dark:text-indigo-300 cursor-help"
                                                      :title="c.quote || ''">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    <span class="truncate max-w-[240px]" x-text="(c.document || 'Dokumen') + ' — hal. ' + c.page"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Recommendations --}}
                                    <div x-show="m.recommendations && m.recommendations.length > 0" class="border-t border-slate-200 dark:border-slate-700 pt-3">
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
                                    <div x-show="m.trends && m.trends.length > 0" class="border-t border-slate-200 dark:border-slate-700 pt-3">
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

                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 text-right">
                                        <span x-text="m.processing_time ? m.processing_time + 's · ' : ''"></span>
                                        <span x-text="m.created_at"></span>
                                        &middot; <a :href="showUrl(m.id)" class="hover:text-indigo-500">detail</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Ask box --}}
            <div class="border-t border-slate-200 dark:border-slate-700 px-6 py-4 bg-slate-50 dark:bg-slate-700/30 rounded-b-2xl">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <textarea x-ref="questionInput"
                                  rows="2"
                                  class="w-full resize-none border border-slate-200 dark:border-slate-600 dark:bg-slate-800 rounded-xl px-4 py-3 text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                  placeholder="Tulis pertanyaan atau minta saran strategis... (Enter kirim, Shift+Enter baris baru)"
                                  :disabled="asking"
                                  x-model="question"
                                  @keydown.enter.prevent="sendQuestion()"></textarea>
                        <p class="text-[11px] text-slate-400 mt-1.5">
                            Jawaban bersumber dari <span class="font-medium" x-text="documents.length"></span> dokumen di knowledge base + tren internet terkini.
                            <span x-show="documents.length === 0" class="text-amber-500">Unggah dokumen dulu sebelum bertanya.</span>
                        </p>
                    </div>
                    <button type="button" @click="sendQuestion()"
                            :disabled="asking || question.trim().length < 5 || documents.length === 0"
                            class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 dark:disabled:bg-slate-600 disabled:cursor-not-allowed text-white rounded-xl text-sm font-medium transition-colors inline-flex items-center gap-2 flex-shrink-0">
                        <svg x-show="!asking" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <svg x-show="asking" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="3" class="opacity-75"/></svg>
                        <span x-text="asking ? '...' : 'Kirim'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function advisorPage() {
    return {
        dragging: false,
        uploading: false,
        uploadElapsed: 0,
        asking: false,
        uploadQueue: [],
        question: '',

        documents: @json($documentsJson),

        messages: @json($messagesJson),

        // ---------- upload ----------
        handleDrop(ev) {
            this.dragging = false;
            this.addFiles(ev.dataTransfer.files);
        },
        handlePick(fileList) {
            this.addFiles(fileList);
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
        addFiles(fileList) {
            for (const f of fileList) {
                if (f.type !== 'application/pdf' && !/\.pdf$/i.test(f.name)) continue;
                if (f.size > 50 * 1024 * 1024) {
                    alert('"' + f.name + '" melebihi 50MB dan diabaikan.');
                    continue;
                }
                this.uploadQueue.push({
                    id: Date.now() + Math.random(),
                    file: f,
                    step: 'queueing',
                    error: null,
                    progress: 0,
                    phase: '',
                });
            }
        },
        // Fase proses backend yang ditampilkan bergilir selama ekstraksi
        // (server tidak streaming progress, jadi ini indikator "berjalan").
        uploadPhases: [
            'Mengunggah file\u2026',
            'Membaca struktur PDF\u2026',
            'Ekstraksi teks per halaman\u2026',
            'Sanitasi & analisis dokumen\u2026',
            'Menyimpan ke knowledge base\u2026',
        ],
        startProgress(f) {
            this.stopProgress(f);
            f.progress = 0;
            f._phaseIdx = 0;
            f.phase = this.uploadPhases[0];
            // Rotasi label fase tiap 3 detik hingga fase terakhir.
            f._phaseTimer = setInterval(() => {
                f._phaseIdx = Math.min((f._phaseIdx ?? 0) + 1, this.uploadPhases.length - 1);
                f.phase = this.uploadPhases[f._phaseIdx];
            }, 3000);
            // Easing menuju 92% — tidak pernah mencapai 100% sebelum respons
            // server, agar terasa progresif namun jujur (belum selesai).
            f._progTimer = setInterval(() => {
                const remaining = 92 - f.progress;
                if (remaining > 0.4) {
                    f.progress = Math.min(92, f.progress + remaining * 0.07 + 0.25);
                }
            }, 200);
        },
        stopProgress(f, done) {
            if (f._phaseTimer) { clearInterval(f._phaseTimer); f._phaseTimer = null; }
            if (f._progTimer)  { clearInterval(f._progTimer);  f._progTimer = null; }
            f.phase = '';
            if (done) f.progress = 100;
        },
        async startUpload() {
            if (this.uploadQueue.length === 0 || this.uploading) return;
            this.uploading = true;
            this.uploadElapsed = 0;
            const elapsedTimer = setInterval(() => this.uploadElapsed++, 1000);

            // Iterasi via indeks: this.uploadQueue[i] mengembalikan referensi
            // ter-proxy Alpine, sehingga mutasi f.step/f.error memicu re-render.
            // (for...of pada array reaktif juga me-return proxy via iterator,
            // tapi akses indeks eksplisit lebih eksplisit & aman.)
            for (let i = 0; i < this.uploadQueue.length; i++) {
                const f = this.uploadQueue[i];
                if (f.step === 'completed') continue;
                f.step = 'uploading';
                f.error = null;
                this.startProgress(f);

                const fd = new FormData();
                fd.append('file', f.file);
                fd.append('_token', this.csrfToken());

                try {
                    // TAHAP 1: upload cepat — server hanya menyimpan file
                    // (menghindari HTTP 504 gateway timeout).
                    const resp = await fetch('{!! route('strategic-advisor.documents.store') !!}', {
                        method: 'POST',
                        body: fd,
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await resp.json().catch(() => ({}));

                    if (! (resp.ok && data.document)) {
                        this.stopProgress(f);
                        f.step = 'failed';
                        f.error = data.error_message || data.message || ('HTTP ' + resp.status);
                        continue;
                    }

                    // TAHAP 2: polling proses chunk demi chunk sampai selesai.
                    // Tiap panggilan hanya ~8 detik di server sehingga tidak
                    // pernah menabrak proxy timeout shared hosting.
                    const processUrl = data.process_url;
                    let guard = 0;
                    let done = false;

                    while (! done) {
                        guard++;
                        if (guard > 3000) {
                            this.stopProgress(f);
                            f.step = 'failed';
                            f.error = 'Proses terlalu lama — dibatalkan.';
                            break;
                        }

                        let pd;
                        try {
                            const pr = await fetch(processUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            pd = await pr.json().catch(() => ({}));
                            if (! pr.ok) {
                                this.stopProgress(f);
                                f.step = 'failed';
                                f.error = pd.error_message || pd.message || ('HTTP ' + pr.status);
                                break;
                            }
                        } catch (err) {
                            // Gangguan jaringan sesaat: tunggu & ulangi.
                            await new Promise(r => setTimeout(r, 1500));
                            continue;
                        }

                        const pagesDone = pd.pages_done || 0;
                        const pagesTotal = pd.total_pages || 0;

                        if (pagesTotal > 0) {
                            // Progres NYATA berdasarkan halaman terekstrak —
                            // hentikan easing/label palsu.
                            this.stopProgress(f);
                            f.progress = Math.min(92, 5 + (pagesDone / pagesTotal) * 87);
                            f.phase = 'Ekstraksi halaman ' + pagesDone + ' / ' + pagesTotal + '\u2026';
                        }

                        if (pd.status === 'completed') {
                            this.stopProgress(f, true);
                            f.step = 'completed';
                            if (pd.document) {
                                // ganti entry yang sama (bila unshift di awal)
                                this.documents = this.documents.filter(x => x.id !== pd.document.id);
                                this.documents.unshift(pd.document);
                            }
                            done = true;
                        } else if (pd.status === 'failed') {
                            this.stopProgress(f);
                            f.step = 'failed';
                            f.error = pd.error_message || pd.document?.error_message || 'Proses gagal.';
                            done = true;
                        } else {
                            // masih 'processing' — jeda kecil lalu poll lagi
                            await new Promise(r => setTimeout(r, 300));
                        }
                    }
                } catch (err) {
                    this.stopProgress(f);
                    f.step = 'failed';
                    f.error = (err && err.message) || 'Network gagal — cek koneksi.';
                }
                // jeda kecil antar file agar tidak menumpuk proses pdftotext
                if (this.uploadQueue.some(q => q.step === 'queueing')) {
                    await new Promise(r => setTimeout(r, 800));
                }
            }

            clearInterval(elapsedTimer);
            this.uploading = false;
            // jeda singkat agar bar hijau 100% sempat terlihat sebelum item
            // sukses dibersihkan dari antrian.
            await new Promise(r => setTimeout(r, 600));
            // bersihkan antrian yang sukses, sisakan yang gagal utk retry manual
            this.uploadQueue = this.uploadQueue.filter(f => f.step === 'failed');
        },
        fError() { return this.uploadQueue.some(f => f.step === 'failed' && f.error); },
        firstError() {
            const f = this.uploadQueue.find(f => f.step === 'failed' && f.error);
            return f ? f.error : '';
        },
        async deleteDocument(d) {
            if (! confirm('Hapus dokumen "' + d.name + '" dari knowledge base? File fisik juga akan dihapus.')) return;
            try {
                const resp = await fetch(d.delete_url, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (resp.ok) {
                    this.documents = this.documents.filter(x => x.id !== d.id);
                } else {
                    alert('Gagal menghapus dokumen (HTTP ' + resp.status + ').');
                }
            } catch (err) {
                alert('Gagal menghapus dokumen: ' + (err.message || err));
            }
        },

        // ---------- ask ----------
        async sendQuestion() {
            const q = this.question.trim();
            if (q.length < 5 || this.asking || this.documents.length === 0) return;
            this.asking = true;
            this.question = '';

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
            // PENTING reaktivitas Alpine: setelah push, akses ulang item via
            // array agar mendapatkan referensi ter-proxy. Memutasi objek
            // mentah (optimistic) TIDAK memicu re-render, sehingga UI stuck
            // di "Mencari halaman relevan..." walau respons sudah diterima.
            const msg = this.messages[this.messages.length - 1];
            this.$nextTick(() => this.scrollToBottom());

            try {
                const fd = new FormData();
                fd.append('question', q);
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
        showUrl(id) {
            return '{!! route('strategic-advisor.show', ':ID') !!}'.replace(':ID', String(id).replace('tmp-', '0'));
        },

        // ---------- utils ----------
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('#advisor-form input[name="_token"]')?.value;
        },
        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(2) + ' MB';
        },
    };
}
</script>
@endpush
@endsection
