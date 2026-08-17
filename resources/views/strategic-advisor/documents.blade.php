@extends('layouts.app')
@section('title', 'Knowledge Base Dokumen')
@section('page-title', 'Strategic Advisor — Dokumen')

@section('content')
<style>
/* ==== Animasi progres upload dokumen ==== */
@keyframes progress-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
/* Track & fill bar progres — CSS murni (bukan utility gradient Tailwind)
   agar pasti tampil di Tailwind v4. */
.progress-track {
    height: 6px;
    border-radius: 9999px;
    background: #e2e8f0;
    overflow: hidden;
}
@media (prefers-color-scheme: dark) {
    .progress-track { background: #475569; }
}
.progress-fill {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, #4f46e5, #8b5cf6, #4f46e5);
    background-size: 200% 100%;
    animation: progress-shimmer 1.4s linear infinite;
    transition: width .3s ease-out;
}
.progress-fill.is-done   { background: #10b981; animation: none; }
.progress-fill.is-failed { background: #f43f5e; animation: none; }
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
</style>
<div class="max-w-7xl mx-auto space-y-6" x-data="advisorDocumentsPage()" x-cloak>

    {{-- Hero --}}
    <div class="glass rounded-2xl border border-white/40 dark:border-slate-700/40 p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-600/10 dark:bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-800 dark:text-white">Knowledge Base Dokumen</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                        Unggah beberapa dokumen strategis (<span class="font-medium">RJPP</span> / <span class="font-medium">MPTI</span> / <span class="font-medium">paper</span>, PDF maks 50MB per file).
                        Sistem mengekstrak &amp; menyimpan isi dokumen <span class="font-medium">per halaman</span> ke knowledge base
                        untuk dijadikan sumber jawaban oleh Strategic Advisor.
                    </p>
                </div>
            </div>
            <a href="{{ route('strategic-advisor.index') }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Tanya Strategic Advisor
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-6 items-start">

        {{-- ================= Upload Card ================= --}}
        <div class="lg:col-span-2 space-y-6">
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
                                    <div class="progress-track">
                                        <div class="progress-fill"
                                             :class="f.step === 'completed' ? 'is-done' : (f.step === 'failed' ? 'is-failed' : '')"
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
        </div>

        {{-- ================= Document Library ================= --}}
        <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Knowledge Base
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300" x-text="documents.length + ' dok'"></span>
                </h3>
            </div>

            <div x-show="documents.length === 0" class="text-center py-12 text-sm text-slate-400 dark:text-slate-500">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Belum ada dokumen. Unggah dokumen untuk memulai.
            </div>

            <div class="space-y-2 max-h-[560px] overflow-y-auto pr-1">
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
                                :disabled="uploading">
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
</div>

@push('scripts')
<script>
function advisorDocumentsPage() {
    return {
        dragging: false,
        uploading: false,
        uploadElapsed: 0,
        uploadQueue: [],

        documents: @json($documentsJson),

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
        // Fase & bobot progres: upload jaringan 2-15%, ekstraksi 15-97%,
        // finalisasi 97-100%. Persentase NYATA di setiap tahap.
        setStage(f, pct, label) {
            f.progress = Math.max(f.progress, Math.min(pct, 97));
            if (label) f.phase = label;
        },
        /** Upload via XHR agar progres jaringan NYATA terlihat
         *  (fetch tidak menyediakan upload progress event). */
        uploadFileXhr(f) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '{!! route('strategic-advisor.documents.store') !!}');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.responseType = 'json';
                xhr.upload.onprogress = (e) => {
                    if (! e.lengthComputable) return;
                    const pct = Math.round((e.loaded / e.total) * 100);
                    this.setStage(f, 2 + pct * 0.13, 'Mengunggah file\u2026');
                };
                xhr.onload = () => resolve({
                    ok: xhr.status >= 200 && xhr.status < 300,
                    status: xhr.status,
                    data: xhr.response || {},
                });
                xhr.onerror = () => reject(new Error('Network gagal saat mengunggah.'));
                xhr.ontimeout = () => reject(new Error('Upload timeout.'));

                const fd = new FormData();
                fd.append('file', f.file);
                fd.append('_token', this.csrfToken());
                xhr.send(fd);
            });
        },
        /** Creep halus di antara dua poll (server memproses ~8 detik per
         *  chunk) supaya bar tidak mati suri — maksimum +4% di atas nilai
         *  nyata terakhir, tidak pernah melewati 92%. */
        startCreep(f) {
            this.stopCreep(f);
            f._creepBase = f.progress;
            f._creepTimer = setInterval(() => {
                if (f.progress < Math.min(f._creepBase + 4, 92)) {
                    f.progress += 0.15;
                }
            }, 250);
        },
        stopCreep(f) {
            if (f._creepTimer) { clearInterval(f._creepTimer); f._creepTimer = null; }
        },
        async startUpload() {
            if (this.uploadQueue.length === 0 || this.uploading) return;
            this.uploading = true;
            this.uploadElapsed = 0;
            const elapsedTimer = setInterval(() => this.uploadElapsed++, 1000);

            // Iterasi via indeks: this.uploadQueue[i] mengembalikan referensi
            // ter-proxy Alpine, sehingga mutasi f.step/f.error memicu re-render.
            for (let i = 0; i < this.uploadQueue.length; i++) {
                const f = this.uploadQueue[i];
                if (f.step === 'completed') continue;
                f.step = 'uploading';
                f.error = null;
                f.progress = 0;
                f.phase = 'Menyiapkan\u2026';

                try {
                    // TAHAP 1 (2-15%): upload file dengan progres jaringan
                    // nyata via XHR — server hanya menyimpan file.
                    const { ok, status, data } = await this.uploadFileXhr(f);

                    if (! (ok && data.document)) {
                        f.step = 'failed';
                        f.error = data.error_message || data.message || ('HTTP ' + status);
                        continue;
                    }

                    // TAHAP 2 (15-97%): polling proses chunk demi chunk sampai
                    // selesai. Tiap panggilan dibuat singkat agar tidak
                    // menabrak proxy timeout shared hosting.
                    const processUrl = data.process_url;
                    let guard = 0;
                    let done = false;

                    while (! done) {
                        guard++;
                        if (guard > 3000) {
                            this.stopCreep(f);
                            f.step = 'failed';
                            f.error = 'Proses terlalu lama — dibatalkan.';
                            break;
                        }

                        let pd;
                        try {
                            this.setStage(f, 15, f.phase || 'Membaca struktur PDF\u2026');
                            this.startCreep(f);
                            const pr = await fetch(processUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            this.stopCreep(f);
                            pd = await pr.json().catch(() => ({}));
                            if (! pr.ok) {
                                f.step = 'failed';
                                f.error = pd.error_message || pd.message || ('HTTP ' + pr.status);
                                break;
                            }
                        } catch (err) {
                            this.stopCreep(f);
                            // Gangguan jaringan sesaat: tunggu & ulangi.
                            await new Promise(r => setTimeout(r, 1500));
                            continue;
                        }

                        const pagesDone = pd.pages_done || 0;
                        const pagesTotal = pd.total_pages || 0;

                        if (pd.status === 'completed') {
                            f.progress = 100;
                            f.phase = 'Tersimpan di knowledge base\u2713';
                            f.step = 'completed';
                            if (pd.document) {
                                this.documents = this.documents.filter(x => x.id !== pd.document.id);
                                this.documents.unshift(pd.document);
                            }
                            done = true;
                        } else if (pd.status === 'failed') {
                            f.step = 'failed';
                            f.error = pd.error_message || pd.document?.error_message || 'Proses gagal.';
                            done = true;
                        } else if (pagesTotal > 0) {
                            // Progres nyata: n dari total halaman -> 15-97%.
                            this.setStage(f, 15 + (pagesDone / pagesTotal) * 82,
                                'Ekstraksi halaman ' + pagesDone + ' / ' + pagesTotal + '\u2026');
                            await new Promise(r => setTimeout(r, 300));
                        } else {
                            this.setStage(f, 15, 'Membaca struktur PDF\u2026');
                            await new Promise(r => setTimeout(r, 300));
                        }
                    }
                } catch (err) {
                    this.stopCreep(f);
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
