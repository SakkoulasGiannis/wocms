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

    {{-- Visual Editor preview integration: when the page is loaded inside the
         visual editor's iframe (?ve=1), clicking ANY section in the rendered
         preview posts a message to the parent so the section's edit panel
         opens automatically. Also handles single-section HTML patches from
         the parent without a full iframe reload. --}}
    @if(request()->has('ve'))
    <style>
        .ve-section {
            cursor: pointer;
            outline: 2px solid transparent;
            outline-offset: 3px;
            transition: outline-color 0.15s;
        }
        .ve-section:hover { outline-color: #c4b5fd !important; }
        .ve-section.ve-active { outline: 2px solid #7c3aed !important; outline-offset: 3px; }
        .ve-section.ve-hidden { opacity: 0.35; outline: 2px dashed #9ca3af !important; }
    </style>
    <script>
        // Click any element → walk up to its section wrapper → tell the parent.
        document.addEventListener('click', function (e) {
            const wrapper = e.target.closest('[data-ve-section-id]');
            if (!wrapper) return;
            e.preventDefault();
            e.stopPropagation();
            const id = parseInt(wrapper.dataset.veSectionId);
            document.querySelectorAll('.ve-section').forEach(w => w.classList.remove('ve-active'));
            wrapper.classList.add('ve-active');
            window.parent.postMessage({ type: 've-section-click', sectionId: id }, '*');
        }, true);

        // Patch a single section's HTML without a full reload.
        window.addEventListener('message', function (e) {
            if (!e.data) return;
            if (e.data.type === 've-patch') {
                const wrapper = document.querySelector('[data-ve-section-id="' + e.data.sectionId + '"]');
                if (!wrapper) return;
                const wasActive = wrapper.classList.contains('ve-active');
                const tmp = document.createElement('div');
                tmp.innerHTML = e.data.html;
                const newEl = tmp.firstElementChild;
                if (newEl) {
                    if (wasActive) newEl.classList.add('ve-active');
                    wrapper.replaceWith(newEl);
                }
            }
        });
    </script>
    @endif
</body>
</html>
