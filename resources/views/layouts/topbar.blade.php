<header class="h-16 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between px-6 flex-shrink-0">
    <div class="flex items-center gap-4">
        <h1 class="text-lg font-semibold text-slate-800 dark:text-white">@yield('page-title', 'Dashboard')</h1>
    </div>

    <div class="flex items-center gap-2">

        {{-- Search --}}
        <div x-data="{ open: false, query: '', results: [], loading: false, timeout: null }" class="relative">
            <div class="flex items-center">
                <div class="relative">
                    <input type="text"
                           x-model="query"
                           @focus="open = true"
                           @keydown.escape="open = false"
                           @input.debounce.300ms="if(query.length >= 2) { loading = true; fetch('{{ route('search') }}?q=' + encodeURIComponent(query)).then(r => r.json()).then(data => { results = data; loading = false; }).catch(() => { results = []; loading = false; }); } else { results = []; }"
                           @click.outside="open = false"
                           placeholder="Search..."
                           class="w-48 lg:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition-all">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <div x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>

            {{-- Search Results Dropdown --}}
            <div x-show="open && query.length >= 2" x-transition
                 class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-1 z-50 max-h-96 overflow-y-auto">
                <template x-if="results.length === 0 && !loading">
                    <div class="px-4 py-6 text-center">
                        <p class="text-sm text-slate-500">No results found</p>
                    </div>
                </template>
                <template x-if="loading">
                    <div class="px-4 py-6 text-center">
                        <p class="text-sm text-slate-500 animate-pulse">Searching...</p>
                    </div>
                </template>
                <template x-for="item in results" :key="item.title + item.type">
                    <a :href="item.url"
                       @click="open = false; query = '';"
                       class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                             :class="'bg-' + item.color + '-100 dark:bg-' + item.color + '-900/30'">
                            <span class="text-xs font-bold"
                                  :class="'text-' + item.color + '-600 dark:text-' + item.color + '-400'"
                                  x-text="item.type.charAt(0)"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-white truncate" x-text="item.title"></p>
                            <p class="text-xs text-slate-500 truncate" x-text="item.subtitle"></p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex-shrink-0" x-text="item.type"></span>
                    </a>
                </template>
            </div>
        </div>

        {{-- AI Status (Dynamic) --}}
        <div x-data="{ status: 'checking', message: 'Checking...' }"
             x-init="fetch('{{ route('api.ai-status') }}').then(r => r.json()).then(data => { status = data.status; message = data.message; }).catch(() => { status = 'offline'; message = 'Connection failed'; })"
             class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition-colors"
             :class="{
                 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300': status === 'online',
                 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300': status === 'offline',
                 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300': status === 'error',
                 'bg-slate-100 dark:bg-slate-700 text-slate-500': status === 'checking'
             }">
            <span class="w-2 h-2 rounded-full"
                  :class="{
                      'bg-emerald-500 animate-pulse': status === 'online',
                      'bg-rose-500': status === 'offline',
                      'bg-amber-500 animate-pulse': status === 'error',
                      'bg-slate-400 animate-pulse': status === 'checking'
                  }"></span>
            <span x-text="status === 'online' ? 'AI Online' : (status === 'offline' ? 'AI Offline' : (status === 'error' ? 'AI Error' : 'Checking...'))"></span>
        </div>

        {{-- Notifications --}}
        <div x-data="{ open: false, notifications: [], unreadCount: 0 }"
             x-init="fetch('{{ route('api.notifications') }}').then(r => r.json()).then(data => { notifications = data.notifications; unreadCount = data.unread_count; })"
             class="relative">
            <button @click="open = !open" class="relative p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span x-show="unreadCount > 0"
                      x-text="unreadCount"
                      class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"></span>
            </button>

            <div x-show="open" @click.outside="open = false" x-transition
                 class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 z-50">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-slate-800 dark:text-white">Notifications</h4>
                    <span x-show="unreadCount > 0" x-text="unreadCount + ' new'" class="text-xs text-indigo-600 dark:text-indigo-400"></span>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <template x-if="notifications.length === 0">
                        <div class="px-4 py-6 text-center">
                            <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <p class="text-sm text-slate-500">No recent notifications</p>
                        </div>
                    </template>
                    <template x-for="notif in notifications" :key="notif.id">
                        <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                 :class="'bg-' + notif.color + '-100 dark:bg-' + notif.color + '-900/30'">
                                <svg class="w-4 h-4" :class="'text-' + notif.color + '-600 dark:text-' + notif.color + '-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <template x-if="notif.icon === 'upload'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></template>
                                    <template x-if="notif.icon === 'ai'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></template>
                                    <template x-if="notif.icon === 'chart'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></template>
                                    <template x-if="notif.icon === 'trash'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></template>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-800 dark:text-white" x-text="notif.message"></p>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="notif.time"></p>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="px-4 py-2.5 border-t border-slate-200 dark:border-slate-700">
                    <a href="{{ route('audit-trail.index') }}" class="block text-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium" @click="open = false">
                        View All Activity
                    </a>
                </div>
            </div>
        </div>

        {{-- Dark Mode Toggle --}}
        <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
            <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </button>

        {{-- User Menu --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                    <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">{{ substr(auth()->user()->name, 0, 1) }}</span>
                </div>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300 hidden lg:inline">{{ auth()->user()->name }}</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open" @click.away="open = false" x-transition
                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-1 z-50">
                <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-700">
                    <p class="text-sm font-medium text-slate-800 dark:text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Settings
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
