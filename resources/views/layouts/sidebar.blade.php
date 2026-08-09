<aside class="fixed inset-y-0 left-0 z-30 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 shadow-sm sidebar-transition overflow-hidden"
       :class="sidebarOpen ? 'w-64' : 'w-20'">
    <div class="flex flex-col h-full">
        {{-- Logo --}}
        <div class="flex items-center h-16 px-4 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('logo/anist.png') }}" alt="AnIST" class="w-10 h-10 rounded-xl object-contain flex-shrink-0">
                    <span x-show="sidebarOpen" x-transition class="font-bold text-lg text-slate-800 dark:text-white whitespace-nowrap">AnIST</span>
                </div>
        </div>

{{-- Navigation --}}
        <nav class="flex-1 py-4 space-y-1 overflow-y-auto px-3">
            @php
            $navItems = [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
                // ['route' => 'kpi-monitoring.index', 'label' => 'KPI Monitoring', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-weight="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
                ['route' => 'measurements.index', 'label' => 'Measurements', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>'],
                ['route' => 'targets.index', 'label' => 'Targets', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>'],
                // ['route' => 'initiatives.index', 'label' => 'Initiatives', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
                ['route' => 'uploads.index', 'label' => 'Upload Evidence', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>'],
                // ['route' => 'ai-analysis.index', 'label' => 'AI Analysis', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
                ['route' => 'reports.index', 'label' => 'Reports', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
                ['route' => 'audit-trail.index', 'label' => 'Log Activity', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            ];

            // Group terpisah — "AI Tools" — fitur yang BUKAN bagian dari workflow
            // KPI Monitoring utama, melainkan modul pendukung berbasis AI
            // grounding. Secara visual dibedakan: label kecil, ikon gradient.
            $aiTools = [
                ['route' => 'strategic-advisor.index', 'label' => 'Strategic Advisor', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>'],
            ];

            $settingsItem = ['route' => 'settings.index', 'label' => 'Settings', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'];
            @endphp

            @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                      {{ request()->routeIs($item['route'] . '*') || (isset($item['route']) && request()->routeIs(str_replace('.index', '.*', $item['route'])))
                         ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                         : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ $item['label'] }}</span>
            </a>
            @endforeach

            {{-- Divider AI Tools --}}
            <div class="pt-4 mt-4 border-t border-slate-200 dark:border-slate-700">
                <p x-show="sidebarOpen" x-transition class="px-3 mb-2 text-[10px] font-bold tracking-widest text-slate-400 dark:text-slate-500 uppercase">AI Tools</p>
                @foreach($aiTools as $tool)
                <a href="{{ route($tool['route']) }}"
                   class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200
                          {{ request()->routeIs($tool['route'] . '*')
                              ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/30'
                              : 'text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20' }}">
                    <span class="w-5 h-5 flex-shrink-0 flex items-center justify-center rounded-md {{ request()->routeIs($tool['route'] . '*') ? 'bg-white/20' : 'bg-indigo-600/10 dark:bg-indigo-500/20' }}">
                        <svg class="w-4 h-4 {{ request()->routeIs($tool['route'] . '*') ? 'text-white' : 'text-indigo-600 dark:text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $tool['icon'] !!}</svg>
                    </span>
                    <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ $tool['label'] }}</span>
                    <span x-show="sidebarOpen" x-transition class="ml-auto text-[9px] font-bold tracking-wide px-1.5 py-0.5 rounded {{ request()->routeIs($tool['route'] . '*') ? 'bg-white/25 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">AI</span>
                </a>
                @endforeach
            </div>

            {{-- Settings (di paling bawah, terpisah) --}}
            <div class="pt-4 mt-auto border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route($settingsItem['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs($settingsItem['route'] . '*')
                              ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $settingsItem['icon'] !!}</svg>
                    <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ $settingsItem['label'] }}</span>
                </a>
            </div>
        </nav>

        {{-- Sidebar Toggle --}}
        <div class="p-3 border-t border-slate-200 dark:border-slate-700">
            <button @click="sidebarOpen = !sidebarOpen"
                    class="flex items-center justify-center w-full px-3 py-2 rounded-xl text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-all">
                <svg x-show="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                <svg x-show="!sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
</aside>
