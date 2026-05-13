<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon --}}
    @if(\App\Models\Setting::get('site_favicon'))
        <link rel="shortcut icon" href="{{ \App\Models\Setting::get('site_favicon') }}">
    @endif

    {{-- SEO Meta Tags --}}
    @if(isset($content) || isset($post))
        <x-seo-meta :entry="$content ?? $post ?? null" :title="$content->title ?? $post->title ?? $title ?? ''" />
    @else
        <title>@yield('title', $title ?? \App\Models\Setting::get('site_name', config('app.name')))</title>
        <meta name="description" content="@yield('description', '')">
    @endif

    {{-- Manrope (homelengo's primary font) — non-blocking load via media swap trick.
         Without `display=swap` browser hides text until font arrives ("FOIT"). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap"></noscript>

    {{-- Pre-built Tailwind CSS via Vite (replaces the @tailwindcss/browser runtime CDN
         that compiled in the browser — saved ~150KB JS + render-blocking compile). --}}
    @vite(['resources/css/frontend.css'])

    {{-- LCP image preload — preload the hero image of the FIRST visible slider/hero
         so it arrives before Largest Contentful Paint. Detected from the first
         home section's image field if present. --}}
    @php
        $lcpImage = null;
        try {
            if (isset($home) && is_object($home)) {
                foreach (['hero_image', 'main_image', 'image', 'featured_image'] as $f) {
                    if (! empty($home->{$f})) {
                        $lcpImage = is_string($home->{$f}) && str_starts_with($home->{$f}, 'http')
                            ? $home->{$f}
                            : asset('storage/' . $home->{$f});
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {}
    @endphp
    @if($lcpImage)
        <link rel="preload" as="image" href="{{ $lcpImage }}" fetchpriority="high">
    @endif

    {{-- Custom Head Scripts from Settings --}}
    @if(\App\Models\Setting::get('custom_head_scripts'))
        {!! \App\Models\Setting::get('custom_head_scripts') !!}
    @endif

    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen bg-white text-slate-800 antialiased font-sans">

    {{-- Admin toolbar (only renders for logged-in admins / editors) --}}
    <x-admin-bar />

    @php $themeManager = app(\App\Services\ThemeManager::class); @endphp

    @include($themeManager->getPartial('header'))

    <main>
        @yield('content')
    </main>

    @include($themeManager->getPartial('footer'))

    {{-- Back to top button --}}
    <button
        x-data="{ visible: false }"
        @scroll.window="visible = window.scrollY > 400"
        x-show="visible"
        x-transition
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-6 right-6 z-40 h-12 w-12 rounded-full bg-brand text-white shadow-lg hover:bg-brand-dark transition-colors flex items-center justify-center"
        aria-label="Back to top"
        style="display:none"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Alpine.js (already bundled with Livewire but being explicit) --}}

    {{-- Custom Body Scripts from Settings --}}
    @if(\App\Models\Setting::get('custom_body_scripts'))
        {!! \App\Models\Setting::get('custom_body_scripts') !!}
    @endif

    @livewireScripts
    @stack('scripts')
</body>
</html>
