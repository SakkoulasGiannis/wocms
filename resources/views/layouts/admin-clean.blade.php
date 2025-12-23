<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js - Load only once -->
    <script>
        // Suppress Alpine multiple instances warning
        window.deferLoadingAlpine = window.deferLoadingAlpine || function (callback) {
            if (!window.Alpine) {
                callback();
            }
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireStyles
    @stack('styles')
</head>
<body class="h-full">
    <div class="flex h-screen bg-gray-50">
        <!-- Left Sidebar Menu -->
        <aside class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
            <div class="p-6">
                <div class="mb-8">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Menu</h2>
                    <nav class="space-y-1">
                        <!-- Dashboard (always first) -->
                        <a href="{{ route('admin.dashboard') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                           <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-3">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                            Dashboard
                        </a>

                        @php
                            $menuTemplates = \App\Models\Template::where('show_in_menu', true)
                                ->where('is_active', true)
                                ->orderBy('menu_order')
                                ->orderBy('name')
                                ->get();

                            // Separate Home from other templates
                            $homeTemplate = $menuTemplates->where('slug', 'home')->first();
                            $otherTemplates = $menuTemplates->where('slug', '!=', 'home');
                        @endphp

                        <!-- Home (always second, after Dashboard) -->
                        @if($homeTemplate)
                            <a href="{{ route('admin.template-entries.index', $homeTemplate->slug) }}"
                               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                      {{ request()->segment(2) == $homeTemplate->slug ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                <x-hero-icon name="{{ $homeTemplate->icon ?: 'home' }}" class="w-5 h-5 mr-3" />
                                {{ $homeTemplate->menu_label ?: $homeTemplate->name }}
                            </a>
                        @endif

                        <!-- Other templates (ordered by menu_order and name) -->
                        @foreach($otherTemplates as $template)
                            <a href="{{ route('admin.template-entries.index', $template->slug) }}"
                               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                      {{ request()->segment(2) == $template->slug ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                <x-hero-icon name="{{ $template->icon ?: 'document-text' }}" class="w-5 h-5 mr-3" />
                                {{ $template->menu_label ?: $template->name }}
                            </a>
                        @endforeach
                    </nav>
                </div>

                @php
                    $menuService = app(\App\Services\MenuService::class);
                    $moduleMenus = $menuService->getModuleMenus();
                @endphp

                @if(count($moduleMenus) > 0)
                    @foreach($moduleMenus as $index => $moduleMenu)
                        <div class="pt-6 border-t border-gray-200" x-data="{ open: true }">
                            <button @click="open = !open" class="w-full flex items-center justify-between text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4 hover:text-gray-600 transition">
                                <span>{{ $moduleMenu['section'] }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <nav class="space-y-1" x-show="open" x-collapse>
                                @foreach($moduleMenu['items'] as $item)
                                    <a href="{{ route($item['route']) }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                              {{ request()->routeIs($item['route'] . '*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                        @if(isset($item['icon']))
                                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        @endif
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    @endforeach
                @endif

                <div class="pt-6 border-t border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Settings</h2>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.content-tree') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.content-tree') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            Content Tree
                        </a>

                        <a href="{{ route('admin.templates.index') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.templates.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-3">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                            </svg>
                            Templates
                        </a>

                        <a href="{{ route('admin.forms.index') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.forms.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Forms
                        </a>

                        <a href="{{ route('admin.media') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.media') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Media Library
                        </a>

                        <a href="{{ route('admin.users') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.users*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Users
                        </a>

                        <a href="{{ route('admin.roles') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.roles*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Roles & Permissions
                        </a>

                        <a href="{{ route('admin.section-templates.index') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.section-templates.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                            </svg>
                            Section Templates
                        </a>

                        <a href="{{ route('admin.modules.index') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.modules.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Modules
                        </a>

                        <a href="{{ route('admin.settings') }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition
                                  {{ request()->routeIs('admin.settings') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Settings
                        </a>

                        @livewire('layout.navigation-logout')
                    </nav>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b border-gray-200">
                <div class="px-6 py-4 flex items-center justify-between">
                    <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200">
                <div class="px-6 py-3">
                    <p class="text-center text-xs text-gray-500">
                        &copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'CMS') }}. All rights reserved.
                        <span class="mx-2">|</span>
                        Built by <a href="https://weborange.gr" target="_blank" class="text-blue-600 hover:text-blue-800">WebOrange.gr</a>
                    </p>
                </div>
            </footer>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')

    <!-- AI Chat Widget -->
    @livewire('admin.a-i-chat.chat-widget')
</body>
</html>
