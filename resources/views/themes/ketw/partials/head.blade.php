<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon --}}
    @include('partials.favicon')

    {{-- Analytics & tracking (Settings → Integrations) --}}
    @include('partials.analytics')

    {{-- SEO Meta Tags — always use the component so empty-string fallbacks kick in correctly --}}
    @php
        $seoEntry = $content ?? $post ?? $property ?? $rentalProperty ?? $home ?? null;
        $seoFallbackTitle = ($content->title ?? $post->title ?? $property->title ?? $rentalProperty->title ?? $home->title ?? null)
            ?: trim((string) View::yieldContent('title'))
            ?: ($title ?? null);
        $seoFallbackDescription = ($content->excerpt ?? $content->description ?? $post->excerpt ?? null)
            ?: trim((string) View::yieldContent('description'))
            ?: \App\Models\Setting::get('site_description', '');
    @endphp
    <x-seo-meta :entry="$seoEntry" :title="$seoFallbackTitle" :description="$seoFallbackDescription" />

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

    @if(\App\Models\Setting::get('ve_tailwind_cdn', false))
        {{-- Tailwind v4 browser CDN (JIT) — the pre-built frontend.css only contains
             classes scanned from source files, so arbitrary utility classes used in
             pasted HTML sections/components (size-*, lg:flex-row, bg-white/3, …) have
             no CSS to resolve to and render unstyled. The browser CDN generates ANY
             utility on demand. Enable via the "Tailwind CDN" toggle in the visual editor. --}}
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" crossorigin="anonymous"></script>
        <style type="text/tailwindcss">
            @import "tailwindcss";
        </style>
        {{-- Heading defaults — Tailwind v4 preflight resets h1-h6 sizes to inherit; restore them.
             :not([class]) ensures these defaults apply only when no user class is set; if the
             user adds Tailwind utilities (e.g. text-3xl) via BlockClassesTune, those win. --}}
        <style>
            html body h1:not([class]) { font-size: 2.5rem  !important; font-weight: 800 !important; line-height: 1.15 !important; margin: 0.5em 0 !important; }
            html body h2:not([class]) { font-size: 2rem    !important; font-weight: 700 !important; line-height: 1.2  !important; margin: 0.5em 0 !important; }
            html body h3:not([class]) { font-size: 1.5rem  !important; font-weight: 700 !important; line-height: 1.25 !important; margin: 0.5em 0 !important; }
            html body h4:not([class]) { font-size: 1.25rem !important; font-weight: 600 !important; line-height: 1.3  !important; margin: 0.5em 0 !important; }
            html body h5:not([class]) { font-size: 1.1rem  !important; font-weight: 600 !important; line-height: 1.4  !important; margin: 0.5em 0 !important; }
            html body h6:not([class]) { font-size: 0.95rem !important; font-weight: 600 !important; line-height: 1.4  !important; margin: 0.5em 0 !important; text-transform: uppercase; letter-spacing: 0.04em; }
            html body p:not([class])  { margin: 0.5em 0 !important; line-height: 1.65 !important; }
            html body ul:not([class]) { padding-left: 1.5rem !important; margin: 0.5em 0 !important; list-style: disc !important; }
            html body ol:not([class]) { padding-left: 1.5rem !important; margin: 0.5em 0 !important; list-style: decimal !important; }
        </style>
    @endif

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

    {{-- Scroll-reveal: elements with .vb-reveal animate in on scroll (progressive
         enhancement — flag set only when IntersectionObserver exists). --}}
    <style>
        html.vb-reveal-on .vb-reveal{opacity:0;transform:translateY(24px);transition:opacity .7s cubic-bezier(.16,1,.3,1),transform .7s cubic-bezier(.16,1,.3,1);will-change:opacity,transform;}
        html.vb-reveal-on .vb-reveal.vb-in{opacity:1;transform:none;}
        @media (prefers-reduced-motion: reduce){html.vb-reveal-on .vb-reveal{opacity:1!important;transform:none!important;transition:none!important;}}
    </style>
    <script>if('IntersectionObserver' in window){document.documentElement.classList.add('vb-reveal-on');}</script>

    {{-- Site-wide custom CSS (Settings → Integrations) — last so it can override theme styles --}}
    @if(\App\Models\Setting::get('site_custom_css'))
        <style id="site-custom-css">{!! \App\Models\Setting::get('site_custom_css') !!}</style>
    @endif
</head>
