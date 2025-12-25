<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - JSON CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-full">
        <nav class="bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-white text-xl font-bold">JSON CMS</h1>
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                @php
                                    $menuService = app(\App\Services\MenuService::class);
                                    $menu = $menuService->getAdminMenu();
                                @endphp

                                @foreach($menu as $section)
                                    @foreach($section['items'] as $item)
                                        <a href="{{ route($item['route']) }}"
                                           class="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium
                                                  {{ request()->routeIs($item['route'] . '*') ? 'bg-gray-900 text-white' : '' }}">
                                            @if(isset($item['icon']))
                                                <i class="fa fa-{{ $item['icon'] }} mr-2"></i>
                                            @endif
                                            {{ $item['label'] }}
                                        </a>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 flex justify-between items-center">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">@yield('header', 'Dashboard')</h1>

                <!-- Cache Clear Button -->
                <button onclick="clearCache()"
                        id="cache-clear-btn"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span id="cache-btn-text">Clear Cache</span>
                </button>
            </div>
        </header>

        <script>
        async function clearCache() {
            const btn = document.getElementById('cache-clear-btn');
            const btnText = document.getElementById('cache-btn-text');
            const originalText = btnText.textContent;

            btn.disabled = true;
            btnText.textContent = 'Clearing...';

            try {
                const response = await fetch('{{ route('admin.cache.clear') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    btnText.textContent = data.message;
                    setTimeout(() => {
                        btnText.textContent = originalText;
                        btn.disabled = false;
                    }, 2000);
                } else {
                    alert(data.message);
                    btnText.textContent = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Cache clear error:', error);
                alert('Error clearing cache');
                btnText.textContent = originalText;
                btn.disabled = false;
            }
        }
        </script>

        <main>
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fa fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fa fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-8">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'CMS') }}. All rights reserved.
                    <span class="mx-2">|</span>
                    Built by <a href="https://weborange.gr" target="_blank" class="text-blue-600 hover:text-blue-800">WebOrange.gr</a>
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
