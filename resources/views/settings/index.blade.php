@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="space-y-6 max-w-4xl">

    {{-- Profile Settings --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Profile</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Update your account information</p>
        </div>
        <form method="POST" action="{{ route('settings.profile') }}" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name</label>
                    <input type="text" name="name" value="{{ $user->name }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition-colors"
                           required>
                    @error('name')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                    <input type="email" name="email" value="{{ $user->email }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition-colors"
                           required>
                    @error('email')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors">
                    Save Profile
                </button>
            </div>
        </form>
    </div>

    {{-- Password Settings --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Password</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Change your password to keep your account secure</p>
        </div>
        <form method="POST" action="{{ route('settings.password') }}" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Current Password</label>
                    <input type="password" name="current_password"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition-colors"
                           required>
                    @error('current_password')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">New Password</label>
                    <input type="password" name="password"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition-colors"
                           required>
                    @error('password')
                    <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition-colors"
                           required>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors">
                    Update Password
                </button>
            </div>
        </form>
    </div>

    {{-- API Configuration --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">API Configuration</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage Google Gemini API integration</p>
        </div>

        {{-- Source indicator: tells admin whether the running system uses the
             key they typed here (database override) or the one in .env /
             config cache. Critical on shared hosting (Hostinger) where
             .env may be read-only and config:cache may be enabled. --}}
        <div class="px-6 pt-4">
            @php
                $sourceBadge = match($keySource) {
                    'database' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-700 dark:text-emerald-300', 'label' => 'DB Override aktif'],
                    'env'      => ['bg' => 'bg-amber-100 dark:bg-amber-900/30',     'text' => 'text-amber-700 dark:text-amber-300',     'label' => 'Memakai .env / config cache'],
                    default    => ['bg' => 'bg-rose-100 dark:bg-rose-900/30',       'text' => 'text-rose-700 dark:text-rose-300',       'label' => 'Belum dikonfigurasi'],
                };
            @endphp
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full font-medium {{ $sourceBadge['bg'] }} {{ $sourceBadge['text'] }}">
                    {{ $sourceBadge['label'] }}
                </span>
                @if($keySource === 'database' && $keyUpdatedAt)
                    <span class="text-slate-400">Updated: {{ $keyUpdatedAt->format('d M Y H:i') }}</span>
                @endif
            </div>
            @if($keySource === 'env')
                <p class="text-xs text-slate-400 mt-2">Key belum di-override via UI. Key yang dipakai adalah dari <code>.env</code> (atau config cache bila <code>config:cache</code>) — pada shared hosting yang read-only, update via form di bawah agar langsung berlaku tanpa restart.</p>
            @endif
        </div>

        <form method="POST" action="{{ route('settings.gemini-key') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Current API Key</label>
                <div class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-slate-500 text-sm font-mono break-all">
                    {{ $maskedKey ?: 'Not configured' }}
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">New API Key</label>
                <input id="gemini_api_key" type="text" name="gemini_api_key" placeholder="Paste new Gemini API key (format AIza...)"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white text-sm font-mono focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition-colors"
                       required>
                @error('gemini_api_key')
                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-400 mt-1">Model: {{ config('services.gemini.model') }}</p>
            </div>

            {{-- Test result placeholder (filled via fetch when Test pressed) --}}
            <div id="geminiTestResult" x-data="{ show: false, ok: false, msg: '', detail: '' }" x-show="show" x-cloak
                 class="rounded-xl border px-4 py-3 text-sm"
                 :class="ok ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300'">
                <div class="flex items-start gap-2">
                    <svg x-show="ok" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg x-show="!ok" class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M12 8v8m0-12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="font-medium" x-text="msg"></p>
                        <p class="text-xs opacity-80 mt-0.5" x-show="detail" x-text="detail"></p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                {{-- Reset to .env (only meaningful when DB override is active) --}}
                @if($keySource === 'database')
                <form method="POST" action="{{ route('settings.gemini-key.reset') }}"
                      onsubmit="return confirm('Hapus override key di database? Sistem kembali memakai key dari .env.');"
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors">
                        Reset ke .env
                    </button>
                </form>
                @endif

                <div class="flex items-center gap-3 ml-auto">
                    {{-- Test key: calls /settings/gemini-key/test which invokes
                         GeminiService::testApiKey() so the admin gets instant
                         feedback before/after saving. --}}
                    <button type="button" onclick="testGeminiKey()"
                            class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-xl transition-colors">
                        Test Key
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-xl transition-colors">
                        Update API Key
                    </button>
                </div>
            </div>
        </form>
    </div>

    @pushonce('scripts')
    <script>
    async function testGeminiKey() {
        const input  = document.getElementById('gemini_api_key');
        const result = document.getElementById('geminiTestResult');
        const key    = (input?.value || '').trim();
        if (key.length < 10) {
            alert('Masukkan API key di field New API Key sebelum menekan Test Key.');
            return;
        }

        const setR = (ok, msg, detail) => {
            try {
                const data = Alpine.$data(result);
                data.show   = true;
                data.ok     = ok;
                data.msg    = msg;
                data.detail = detail || '';
            } catch (e) {
                // Fallback: show as plain text if Alpine is not ready.
                result.classList.remove('hidden');
                result.innerHTML = '<p class="font-medium">' + msg + '</p>' + (detail ? '<p class="text-xs opacity-80 mt-0.5">' + detail + '</p>' : '');
            }
        };

        setR(false, 'Menguji koneksi ke Gemini...', '');
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value;
            const fd = new FormData();
            fd.append('gemini_api_key', key);

            const resp = await fetch('{{ route("settings.gemini-key.test") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await resp.json();
            setR(!!data.success, data.message || (data.success ? 'OK' : 'Gagal'), data.detail || '');
        } catch (e) {
            setR(false, 'Tidak dapat menguji key: ' + e.message, '');
        }
    }
    </script>
    @endpushonce

    {{-- System Information --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">System Information</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Runtime environment details</p>
            </div>
            <button type="button" onclick="loadSystemInfo()" class="px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                Refresh
            </button>
        </div>
        <div id="systemInfo" class="p-6">
            <p class="text-sm text-slate-500">Click "Refresh" to load system information.</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function loadSystemInfo() {
    const container = document.getElementById('systemInfo');
    container.innerHTML = '<p class="text-sm text-slate-500 animate-pulse">Loading...</p>';

    fetch('{{ route("settings.system-info") }}')
        .then(res => res.json())
        .then(data => {
            const items = [
                { label: 'Laravel Version', value: data.laravel_version, color: 'indigo' },
                { label: 'PHP Version', value: data.php_version, color: 'violet' },
                { label: 'Database', value: data.db_driver + ' (' + data.db_connection + ')', color: 'emerald' },
                { label: 'Queue', value: data.queue_connection + ' — ' + data.queue_status, color: 'cyan' },
                { label: 'Gemini Model', value: data.gemini_model, color: 'amber' },
                { label: 'Storage Writable', value: data.storage_writable ? 'Yes' : 'No', color: data.storage_writable ? 'emerald' : 'rose' },
            ];

            let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
            items.forEach(item => {
                html += `
                    <div class="px-4 py-3 rounded-xl bg-${item.color}-50 dark:bg-${item.color}-900/20 border border-${item.color}-100 dark:border-${item.color}-900/30">
                        <p class="text-xs text-${item.color}-600 dark:text-${item.color}-400 font-medium">${item.label}</p>
                        <p class="text-sm font-semibold text-slate-800 dark:text-white mt-1">${item.value}</p>
                    </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(() => {
            container.innerHTML = '<p class="text-sm text-rose-500">Failed to load system information.</p>';
        });
}
</script>
@endpush
