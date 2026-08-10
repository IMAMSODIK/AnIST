@extends('layouts.app')
@section('title', 'Strategic Advisor')
@section('page-title', 'Strategic Advisor')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="strategicAdvisor()" x-cloak>

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
                    Upload satu atau beberapa dokumen strategis (<span class="font-medium">RJPP</span> / <span class="font-medium">MPTI</span> / <span class="font-medium">research paper</span>) format PDF.
                    AI akan mengekstrak struktur dokumen, memberikan analisis &amp; rekomendasi strategis berdasarkan dokumen,
                    serta menyarankan tren internet terkini yang relevan dengan domain dokumen tersebut (via Google Search grounding).
                </p>
            </div>
        </div>
    </div>

    {{-- Upload Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-2 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Upload Dokumen Strategis
        </h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Hanya file PDF, maksimal 20MB per file. Boleh beberapa file sekaligus. Proses analisis ~15-45 detik per dokumen.</p>

        {{-- Dropzone multi-file (NO native <form> submit; we never submit this form,
             upload handled via AJAX to /upload-ajax one file at a time) --}}
        <form id="strategic-advisor-form" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Dokumen PDF (boleh lebih dari satu)</label>
                <div class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                     :class="dragging ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-400 dark:hover:border-indigo-500'"
                     @click="$refs.fileInput.click()"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="handleDrop($event)">
                    <input type="file" x-ref="fileInput" class="hidden" accept=".pdf,application/pdf" multiple
                           @change="handlePick($event.target.files)">
                    <svg class="w-10 h-10 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Click untuk pilih file atau drag &amp; drop ke sini <span class="text-xs text-slate-400">(boleh beberapa PDF sekaligus)</span></p>
                    <p class="text-xs text-slate-400 mt-2">PDF hingga 20MB per file</p>
                </div>
            </div>

            {{-- Selected files preview --}}
            <div x-show="queue.length > 0" x-transition class="space-y-2">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Antrian upload (<span x-text="queue.length"></span>):</p>
                <template x-for="(f, i) in queue" :key="f.id">
                    <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-700/30 rounded-xl px-4 py-2.5 border border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span class="text-sm text-slate-700 dark:text-slate-200 truncate" x-text="f.file.name"></span>
                            <span class="text-xs text-slate-400 flex-shrink-0" x-text="formatSize(f.file.size)"></span>
                        </div>
                        <button type="button" @click="removeFile(i)" class="text-slate-400 hover:text-rose-500 flex-shrink-0" x-show="!uploading">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- How it works --}}
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-sm text-indigo-700 dark:text-indigo-300">
                        <p class="font-medium mb-1">Alur:</p>
                        <ol class="list-decimal list-inside space-y-1 text-xs">
                            <li>Pilih satu atau beberapa PDF — sistem memproses satu-per-satu agar progres terlihat</li>
                            <li>DocumentExtractorService ekstrak struktur (Daftar Isi, KPI, Inisiatif, SS, Ringkasan Eksekutif)</li>
                            <li>PromptManager susun Strategic Advisor Prompt</li>
                            <li>Gemini menganalisis dengan <span class="font-medium">Google Search grounding</span> untuk tren internet terkini</li>
                            <li>Selesai → langsung tautan ke halaman detail hasil</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center pt-4">
                <a href="{{ route('strategic-advisor.history') }}" class="text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Riwayat analisis
                </a>
                <div class="flex items-center gap-3">
                    <button type="button" @click="resetQueue()" x-show="queue.length > 0 && !uploading" class="text-sm text-slate-500 hover:text-rose-500 dark:text-slate-400 dark:hover:text-rose-400">
                        Kosongkan
                    </button>
                    <button type="button" @click="startUpload()"
                            :disabled="queue.length === 0 || uploading"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 dark:disabled:bg-slate-600 disabled:cursor-not-allowed text-white rounded-xl text-sm font-medium transition-colors inline-flex items-center gap-2">
                        <svg x-show="!uploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <svg x-show="uploading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="3" class="opacity-75"/></svg>
                        <span x-text="uploading ? 'Memproses...' : 'Analyze'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Aktivitas Terkini
            </h3>
            @if($recent->count() > 0)
            <a href="{{ route('strategic-advisor.history') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Lihat semua →</a>
            @endif
        </div>

        @if($recent->isEmpty())
        <div class="text-center py-10 text-sm text-slate-400 dark:text-slate-500">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Belum ada analisis. Upload dokumen strategis untuk memulai.
        </div>
        @else
        <div class="space-y-3">
            @foreach($recent as $r)
            <a href="{{ route('strategic-advisor.show', $r) }}" class="block bg-slate-50 dark:bg-slate-700/30 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl p-4 transition-colors group">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800 dark:text-white truncate group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{ $r->source_file }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $r->document_type !== 'unknown' ? strtoupper($r->document_type) : 'Tipe tidak dikenali' }}
                            @if($r->company) &middot; {{ $r->company }} @endif
                            @if($r->period) &middot; {{ $r->period }} @endif
                        </p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $r->created_at->diffForHumans() }} &middot; oleh {{ $r->user->name ?? 'N/A' }}</p>
                    </div>
                    <span class="inline-flex flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-medium
                        {{ $r->status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}
                        {{ $r->status === 'processing' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                        {{ $r->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : '' }}
                        {{ $r->status === 'pending' ? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' : '' }}">
                        @if($r->status === 'completed')Selesai
                        @elseif($r->status === 'processing')Memproses
                        @elseif($r->status === 'failed')Gagal
                        @else Pending @endif
                    </span>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Modal progress upload --}}
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="modalOpen = false" style="display:none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Proses Analisis Dokumen
                </h3>
                <button @click="closeModal()" x-show="!uploading && doneCount === queue.length" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Overall progress --}}
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Total Progres</span>
                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                        <span x-text="doneCount"></span>/<span x-text="queue.length"></span> dokumen
                    </span>
                </div>
                <div class="h-2.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-500 rounded-full relative" :style="`width: ${overallPct}%`">
                        <div x-show="uploading" class="absolute inset-0 bg-white/30 animate-pulse rounded-full"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-show="uploading">
                        Sedang memproses: <span class="font-medium text-slate-700 dark:text-slate-200" x-text="currentFileName"></span>
                    </p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400" x-show="!uploading && doneCount === queue.length">
                        Selesai. Berkas siap dilihat.
                    </p>
                    <p class="text-xs text-slate-400 ml-auto" x-text="`${Math.round(overallPct)}%`"></p>
                </div>
            </div>

            {{-- Per-file list --}}
            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3 max-h-[50vh]">
                <template x-for="(f, i) in queue" :key="f.id">
                    <div class="border border-slate-100 dark:border-slate-700 rounded-xl p-3">
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                  :class="{
                                      'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300': f.step === 'queueing',
                                      'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 animate-pulse': f.step === 'uploading',
                                      'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': f.step === 'completed',
                                      'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300': f.step === 'failed'
                                  }">
                                <span x-show="f.step === 'queueing'" x-text="i + 1"></span>
                                <svg x-show="f.step === 'uploading'" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke-width="3" class="opacity-75"/></svg>
                                <svg x-show="f.step === 'completed'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="f.step === 'failed'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate" x-text="f.file.name"></p>
                                <div class="mt-1.5 space-y-1">
                                    {{-- indeterminate uploader (single-segment) untuk Tahap 1 (extract+ground) --}}
                                    <template x-for="(s, si) in f.stages" :key="si">
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                  :class="{
                                                      'bg-slate-300 dark:bg-slate-600': s.state === 'pending',
                                                      'bg-amber-500 animate-pulse': s.state === 'running',
                                                      'bg-emerald-500': s.state === 'done',
                                                      'bg-rose-500': s.state === 'failed'
                                                  }"></span>
                                            <span class="text-slate-500 dark:text-slate-400" :class="s.state === 'running' ? 'font-medium text-amber-700 dark:text-amber-400' : ''" x-text="s.label"></span>
                                        </div>
                                    </template>
                                </div>
                                @if(false)
                                <div class="mt-2 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 transition-all duration-300" :style="`width: ${f.pct}%`"></div>
                                </div>
                                @endif
                                <p x-show="f.error" class="text-xs text-rose-500 dark:text-rose-400 mt-1.5" x-text="f.error"></p>
                                <a x-show="f.show_url && f.step === 'completed'"
                                   :href="f.show_url"
                                   class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-1.5 inline-flex items-center gap-1">
                                    Lihat hasil →
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between bg-slate-50 dark:bg-slate-700/30">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    <span x-show="uploading">Mohon tunggu, jangan tutup halaman...</span>
                    <span x-show="!uploading && doneCount < queue.length && doneCount > 0">Sebagian dokumen gagal. Anda bisa tutup modal ini.</span>
                    <span x-show="!uploading && doneCount === queue.length && queue.length > 0">
                        <span x-text="successCount"></span> berhasil, <span x-text="queue.length - successCount"></span> gagal dari <span x-text="queue.length"></span> dokumen.
                    </span>
                </p>
                <div class="flex items-center gap-2">
                    <button @click="closeModal()" x-show="!uploading"
                            class="px-4 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Tutup
                    </button>
                    <button @click="resetCompletedAndRetry()" x-show="!uploading && failedCount > 0"
                            class="px-4 py-2 text-sm bg-amber-600 hover:bg-amber-700 text-white rounded-xl transition-colors inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.582m0 0a8.003 8.003 0 01-15.357-2m15.357 9H15"/></svg>
                        Retry gagal
                    </button>
                    <button @click="goToHistory()" x-show="!uploading && successCount > 0"
                            class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Buka riwayat
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
/**
 * Strategic Advisor state machine (Alpine).
 *
 * - queue: file list with per-file stages ('queueing','uploading','completed','failed')
 * - Upload is sequential (one file at a time) — each file hits /strategic-advisor/upload-ajax
 *   with multipart/form-data. We do NOT use XHR.upload.onprogress because the heavy
 *   work happens server-side (extraction + Gemini grounded call ~15-45s) and the actual
 *   network upload is fast; instead we animate a 3-stage indicator:
 *     1) "Mengirim dokumen"
 *     2) "Ekstraksi struktur dokumen"
 *     3) "AI analisis + grounding tren internet"
 *
 * Each stage has state: pending | running | done | failed. We move the stage to 'running'
 * when XHR fires onloadstart (request sent) and to 'done' on success. When server returns
 * JSON, we deterministically mark stage 2 + stage 3 as done OR failed based on response.status.
 * This gives the user a truthful, non-fake sense of progress without needing a Streamed
 * response or polling.
 */
function strategicAdvisor() {
    return {
        dragging: false,
        uploading: false,
        modalOpen: false,
        queue: [],
        doneCount: 0,
        successCount: 0,
        currentFileName: '',

        get overallPct() {
            if (this.queue.length === 0) return 0;
            // weight: queueing=0, uploading=50, completed/failed=100
            let sum = 0;
            for (const f of this.queue) {
                if (f.step === 'completed' || f.step === 'failed') sum += 100;
                else if (f.step === 'uploading') {
                    // count stages done
                    const stagesDone = f.stages.filter(s => s.state === 'done').length;
                    sum += (stagesDone / f.stages.length) * 100;
                }
            }
            return sum / this.queue.length;
        },
        get failedCount() {
            return this.queue.filter(f => f.step === 'failed').length;
        },

        handleDrop(ev) {
            this.dragging = false;
            this.addFiles(ev.dataTransfer.files);
        },
        handlePick(fileList) {
            this.addFiles(fileList);
            // reset input so picking the same file twice still triggers @change
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
        addFiles(fileList) {
            for (const f of fileList) {
                if (f.type !== 'application/pdf' && !/\.pdf$/i.test(f.name)) {
                    continue;
                }
                if (f.size > 20 * 1024 * 1024) {
                    alert(`"${f.name}" melebihi 20MB dan diabaikan.`);
                    continue;
                }
                this.queue.push({
                    id: Date.now() + Math.random(),
                    file: f,
                    step: 'queueing', // queueing | uploading | completed | failed
                    pct: 0,
                    show_url: null,
                    error: null,
                    stages: [
                        { label: 'Mengirim dokumen ke server', state: 'pending' },
                        { label: 'Ekstraksi struktur dokumen', state: 'pending' },
                        { label: 'AI analisis + grounding tren internet', state: 'pending' },
                    ],
                });
            }
        },
        removeFile(i) {
            this.queue.splice(i, 1);
        },
        resetQueue() {
            if (this.uploading) return;
            this.queue = [];
            this.doneCount = 0;
            this.successCount = 0;
        },
        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(2) + ' MB';
        },

        async startUpload() {
            if (this.queue.length === 0) return;
            if (this.uploading) return;
            this.uploading = true;
            this.modalOpen = true;
            this.doneCount = 0;
            this.successCount = 0;

            // process sequentially
            for (const f of this.queue) {
                if (f.step === 'completed') continue;
                f.step = 'uploading';
                f.error = null;
                this.currentFileName = f.file.name;

                // mark stage 0 running
                f.stages[0].state = 'running';

                const fd = new FormData();
                fd.append('file', f.file);
                fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('#strategic-advisor-form input[name="_token"]')?.value);

                try {
                    const resp = await fetch('{!! route('strategic-advisor.upload-ajax') !!}', {
                        method: 'POST',
                        body: fd,
                        headers: { 'Accept': 'application/json' },
                    });

                    f.stages[0].state = 'done';
                    f.stages[1].state = 'done';
                    f.stages[2].state = 'running';

                    const data = await resp.json().catch(() => ({}));

                    if (resp.ok && data.status === 'completed') {
                        f.stages[2].state = 'done';
                        f.step = 'completed';
                        f.show_url = data.show_url || null;
                        this.successCount++;
                    } else {
                        f.stages[2].state = 'failed';
                        f.step = 'failed';
                        f.error = data.error_message || ('HTTP ' + resp.status + ' — ' + (data.message || 'Gagal tak terduga'));
                    }
                } catch (err) {
                    if (f.stages[0].state === 'running') f.stages[0].state = 'failed';
                    if (f.stages[1].state === 'running') f.stages[1].state = 'failed';
                    if (f.stages[2].state === 'running') f.stages[2].state = 'failed';
                    f.step = 'failed';
                    f.error = (err && err.message) || 'Network gagal — cek koneksi.';
                }
                this.doneCount++;
                // Jeda 4 detik antar file agar tidak memukul habis kuota
                // Gemini free tier (20 req/menit). Multi-upload 5 file = 20 req
                // spread over ~80-120s, aman di bawah 20 RPM.
                if (this.doneCount < this.queue.length) {
                    await new Promise(r => setTimeout(r, 4000));
                }
            }

            this.uploading = false;
            this.currentFileName = '';
        },

        closeModal() {
            this.modalOpen = false;
            // refresh halaman untuk menampilkan hasil terbaru di Recent Activity
            if (! this.uploading && this.successCount > 0) {
                setTimeout(() => location.reload(), 200);
            }
        },
        goToHistory() {
            location.href = '{!! route('strategic-advisor.history') !!}';
        },
        resetCompletedAndRetry() {
            // Reset failed items; remove successful from queue.
            // Keep IDs from failed ones so retry re-processes only them.
            const failedOnly = this.queue.filter(f => f.step === 'failed');
            if (failedOnly.length === 0) return;
            for (const f of failedOnly) {
                f.step = 'queueing';
                f.error = null;
                f.show_url = null;
                f.stages = [
                    { label: 'Mengirim dokumen ke server', state: 'pending' },
                    { label: 'Ekstraksi struktur dokumen', state: 'pending' },
                    { label: 'AI analisis + grounding tren internet', state: 'pending' },
                ];
            }
            this.queue = failedOnly;
            this.doneCount = 0;
            this.successCount = 0;
            this.startUpload();
        },
    };
}
</script>
@endpush
@endsection
