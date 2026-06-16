{{--
    Portal header — a DISTINCT header for portal/sub-section pages.
    Pulls its own menu by slug ('portal') via FrontendMenuService::get(),
    so it's completely independent from the site's main header menu.
    Clone this file to build other custom headers.
--}}
@php
    $portalMenu = app(\App\Services\FrontendMenuService::class)->get('portal');
@endphp
<header class="sticky top-0 z-40 bg-on-surface text-white shadow-soft">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 sm:px-6 lg:px-8">
        {{-- Brand --}}
        <a href="/" class="flex items-center gap-2 text-lg font-extrabold tracking-tight">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white">P</span>
            <span>{{ \App\Models\Setting::get('site_name', 'Portal') }}</span>
            <span class="ml-1 rounded bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider">Portal</span>
        </a>

        {{-- Portal menu (its own menu, by slug) --}}
        <nav class="hidden items-center gap-6 md:flex">
            @if($portalMenu)
                @foreach($portalMenu->rootItems as $item)
                    <a href="{{ $item->resolved_url }}"
                       @if($item->target) target="{{ $item->target }}" @endif
                       class="text-sm font-medium text-white/80 transition-colors hover:text-white">
                        {{ $item->title }}
                    </a>
                @endforeach
            @else
                <span class="text-xs text-white/50">Create a menu with slug “portal” to populate this nav.</span>
            @endif
        </nav>

        <a href="/" class="text-sm font-semibold text-white/70 transition-colors hover:text-white">← Main site</a>
    </div>
</header>
