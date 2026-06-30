<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    @if(\App\Models\Setting::get('site_favicon'))
        <link rel="icon" type="image/x-icon" href="{{ \App\Models\Setting::get('site_favicon') }}">
    @endif

    {{-- SEO Meta Tags --}}
    @if(isset($content) || isset($post))
        <x-seo-meta :entry="$content ?? $post ?? null" :title="$content->title ?? $post->title ?? $title ?? ''" />
    @else
        <title>@yield('title', $title ?? config('app.name'))</title>
        <meta name="description" content="@yield('description', '')">
    @endif

    {{-- Theme Assets --}}
    @php
        $themeManager = app(\App\Services\ThemeManager::class);
    @endphp

    @if($themeManager->usesVite())
        {{-- Vite Assets (CSS & JS from npm run build) --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Tailwind Plus Elements removed: no <el-*> component is used on the frontend,
             it was ~492 KiB of unused JS (Lighthouse). Re-add per-page if ever needed. --}}
    @else
        {{-- Theme CSS Assets --}}
        {!! $themeManager->renderCssAssets() !!}
    @endif

    @if(\App\Models\Setting::get('ve_tailwind_cdn', false))
        {{-- Tailwind v4 browser CDN — renders Tailwind classes in sections site-wide --}}
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" crossorigin="anonymous"></script>
        <style type="text/tailwindcss">
            @import "tailwindcss";
        </style>
    @endif

    {{-- Livewire Styles --}}
    @livewireStyles

    @stack('styles')

    {{-- Site-wide custom CSS (Settings → Integrations) — last so it can override theme styles --}}
    @if(\App\Models\Setting::get('site_custom_css'))
        <style id="site-custom-css">{!! \App\Models\Setting::get('site_custom_css') !!}</style>
    @endif
</head>
<body class="bg-gray-50">
    @include($themeManager->getPartial('header'))

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    @include($themeManager->getPartial('footer'))

    {{-- Theme JS Assets --}}
    @if(!$themeManager->usesVite())
        {!! $themeManager->renderJsAssets() !!}
    @endif

    {{-- Livewire Scripts --}}
    @livewireScripts

    @stack('scripts')
</body>
</html>
