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
        <form method="POST" action="{{ route('settings.gemini-key') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Current API Key</label>
                <div class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-slate-500 text-sm font-mono">
                    {{ $maskedKey ?: 'Not configured' }}
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">New API Key</label>
                <input type="text" name="gemini_api_key" placeholder="Enter new Gemini API key"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white text-sm font-mono focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition-colors"
                       required>
                @error('gemini_api_key')
                <p class="text-rose-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-400 mt-1">Model: {{ config('services.gemini.model') }}</p>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-xl transition-colors">
                    Update API Key
                </button>
            </div>
        </form>
    </div>

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
