<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-md w-full text-center">
            <div class="w-20 h-20 rounded-2xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-2.586a.714.714 0 01.021-.187l.8-.8a1.5 1.5 0 000-2.152l-.8-.8A.714.714 0 0120 10.586V8a2 2 0 00-2-2h-2.586a.714.714 0 01-.187-.021l-.8-.8a1.5 1.5 0 00-2.152 0l-.8.8A.714.714 0 0110.586 6H8a2 2 0 00-2 2v2.586a.714.714 0 01-.021.187l-.8.8a1.5 1.5 0 000 2.152l.8.8a.714.714 0 01.021.187V17a2 2 0 002 2z"/>
                </svg>
            </div>

            <p class="text-6xl font-extrabold text-slate-200 dark:text-slate-700 mb-2">403</p>
            <h1 class="text-xl font-bold text-slate-800 dark:text-white">Akses Ditolak</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                {{ $exception->getMessage() ?: 'Anda tidak memiliki akses ke halaman ini.' }}
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                @auth
                <a href="{{ route('strategic-advisor.index') }}"
                   class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    Kembali ke Strategic Advisor
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('dashboard') }}"
                   class="w-full sm:w-auto px-5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-medium transition-colors inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit"
                            class="w-full px-5 py-2.5 text-slate-500 hover:text-rose-500 dark:text-slate-400 dark:hover:text-rose-400 rounded-xl text-sm font-medium transition-colors inline-flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Logout
                    </button>
                </form>
                @endauth
            </div>
        </div>
    </div>
</body>
</html>
