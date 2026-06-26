<!-- Header -->
<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                @php
                    $siteName = \App\Models\Setting::get('site_name', 'CMS');
                    $siteLogo = \App\Models\Setting::get('site_logo', '');
                @endphp

                <a href="/" class="flex items-center space-x-3">
                    @if($siteLogo)
                        <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-8 w-auto">
                    @endif
                    <span class="text-2xl font-bold text-orange-600">{{ $siteName }}</span>
                </a>
            </div>
            @php($headerMenu = app(\App\Services\FrontendMenuService::class)->get('header'))
            <nav class="hidden md:flex items-center space-x-6">
                @forelse(optional($headerMenu)->rootItems ?? [] as $item)
                    @if($item->children->count())
                        <div class="relative group">
                            <a href="{{ $item->resolved_url }}" class="text-gray-700 hover:text-orange-600 inline-flex items-center gap-1">
                                {{ $item->title }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </a>
                            <div class="absolute left-0 top-full pt-2 hidden group-hover:block min-w-[15rem] z-50">
                                <div class="bg-white rounded-lg shadow-lg border border-gray-100 py-2">
                                    @foreach($item->children as $child)
                                        <a href="{{ $child->resolved_url }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-orange-600">{{ $child->title }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ $item->resolved_url }}" class="text-gray-700 hover:text-orange-600">{{ $item->title }}</a>
                    @endif
                @empty
                    <a href="/" class="text-gray-700 hover:text-orange-600">Home</a>
                    <a href="/blog" class="text-gray-700 hover:text-orange-600">Blog</a>
                @endforelse
                @auth
                    <a href="/admin" class="text-gray-700 hover:text-orange-600">Admin</a>
                @else
                    <a href="/login" class="text-gray-700 hover:text-orange-600">Login</a>
                @endauth
            </nav>
        </div>
    </div>
</header>
