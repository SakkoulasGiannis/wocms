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

    {{-- Manrope (homelengo's primary font) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Tailwind CSS v4 browser CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" crossorigin="anonymous"></script>
    <style type="text/tailwindcss">
        @import "tailwindcss";

        @theme {
            /* Brand colors — exact homelengo design tokens (scss/abstracts/_variable.scss) */
            --color-brand:        #1563df;  /* primary */
            --color-brand-hover:  #0e49a6;  /* primary-hover */
            --color-brand-soft:   #f3f7fd;  /* primary-new (light tint) */
            --color-brand-dark:   #0e49a6;  /* alias for backwards compat */
            --color-brand-light:  #f3f7fd;  /* alias for backwards compat */

            --color-critical:     #c72929;
            --color-yellow:       #ffa800;
            --color-success:      #198754;

            --color-on-surface:   #161e2d;  /* dark text */
            --color-surface:      #f7f7f7;  /* light gray bg */
            --color-outline:      #e4e4e4;  /* borders */
            --color-variant-1:    #5c6368;  /* medium text */
            --color-variant-2:    #a3abb0;  /* light text */
            --color-variant-3:    #8e8e93;

            /* Shadows from homelengo */
            --shadow-card:    0px 4px 18px 0px #00000014;
            --shadow-soft:    0px 10px 25px 0px #365f681a;
            --shadow-strong:  0px 30px 60px 0px #0000001a;

            /* Typography */
            --font-sans: "Manrope", ui-sans-serif, system-ui, sans-serif;

            --container-8xl: 88rem;
        }

        /* Apply Manrope to body so existing components inherit it */
        body { font-family: "Manrope", ui-sans-serif, system-ui, sans-serif; }
    </style>

    {{-- Heading defaults — Tailwind v4 preflight resets h1-h6 to inherit; restore them here.
         :not([class]) keeps these defaults out of the way when the user explicitly applies
         Tailwind utilities (text-3xl, font-bold, etc.) via BlockClassesTune in the editor. --}}
    <style>
        html body h1:not([class]) { font-size: 2.5rem  !important; font-weight: 800 !important; line-height: 1.15 !important; margin: 0.5em 0 !important; color: #0f172a; }
        html body h2:not([class]) { font-size: 2rem    !important; font-weight: 700 !important; line-height: 1.2  !important; margin: 0.5em 0 !important; color: #0f172a; }
        html body h3:not([class]) { font-size: 1.5rem  !important; font-weight: 700 !important; line-height: 1.25 !important; margin: 0.5em 0 !important; color: #1f2937; }
        html body h4:not([class]) { font-size: 1.25rem !important; font-weight: 600 !important; line-height: 1.3  !important; margin: 0.5em 0 !important; color: #1f2937; }
        html body h5:not([class]) { font-size: 1.1rem  !important; font-weight: 600 !important; line-height: 1.4  !important; margin: 0.5em 0 !important; color: #374151; }
        html body h6:not([class]) { font-size: 0.95rem !important; font-weight: 600 !important; line-height: 1.4  !important; margin: 0.5em 0 !important; color: #4b5563; text-transform: uppercase; letter-spacing: 0.04em; }
        html body p:not([class])  { margin: 0.5em 0 !important; line-height: 1.65 !important; }
        html body ul:not([class]) { padding-left: 1.5rem !important; margin: 0.5em 0 !important; list-style: disc !important; }
        html body ol:not([class]) { padding-left: 1.5rem !important; margin: 0.5em 0 !important; list-style: decimal !important; }

        /* ── Buttons (homelengo .tf-btn spec) ─────────────────────────────────── */
        .tf-btn {
            display: inline-flex; justify-content: center; align-items: center; gap: 8px;
            padding: 10px 20px; min-width: 162px; min-height: 54px;
            border-radius: 9999px;
            font-size: 16px; line-height: 21.86px; font-weight: 600;
            background-color: #ffffff; color: #161e2d;
            border: 1px solid #161e2d;
            text-decoration: none;
            transition: background-color .3s ease, color .3s ease, border-color .3s ease;
        }
        .tf-btn svg { width: 20px; flex-shrink: 0; }
        .tf-btn:hover { background-color: #1563df; color: #ffffff; border-color: #1563df; }
        .tf-btn.primary { background-color: #1563df; color: #ffffff; border-color: #1563df; }
        .tf-btn.primary:hover { background-color: #0e49a6; border-color: #0e49a6; }
        .tf-btn.size-1 { padding: 11px 36px; min-width: 244px; }
        .tf-btn.size-2 { padding: 11px 40px; }

        /* "Read more" link variant */
        .btn-read-more {
            display: inline-block;
            font-size: 16px; line-height: 26px; font-weight: 700;
            color: #161e2d;
            text-transform: capitalize;
            border-bottom: 2px solid #161e2d;
            padding: 0 0 4px 0;
        }
        .btn-read-more:hover { color: #1563df; border-color: #1563df; }
    </style>

    {{-- Custom Head Scripts from Settings --}}
    @if(\App\Models\Setting::get('custom_head_scripts'))
        {!! \App\Models\Setting::get('custom_head_scripts') !!}
    @endif

    @livewireStyles
    @stack('styles')
</head>
<body class="min-h-screen bg-white text-slate-800 antialiased font-sans">

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
