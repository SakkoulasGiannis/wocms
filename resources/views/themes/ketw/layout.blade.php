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

    {{-- Tailwind CSS v4 browser CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" crossorigin="anonymous"></script>
    <style type="text/tailwindcss">
        @import "tailwindcss";

        @theme {
            --color-brand: oklch(0.45 0.15 245);
            --color-brand-light: oklch(0.55 0.13 245);
            --color-brand-dark: oklch(0.35 0.13 245);

            --container-8xl: 88rem;
        }
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
