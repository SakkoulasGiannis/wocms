<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!-- Favicon -->
    @if(\App\Models\Setting::get('site_favicon'))
        <link rel="shortcut icon" href="{{ \App\Models\Setting::get('site_favicon') }}">
        <link rel="apple-touch-icon-precomposed" href="{{ \App\Models\Setting::get('site_favicon') }}">
    @else
        <link rel="shortcut icon" href="/themes/bootstrap/images/logo/favicon.png">
        <link rel="apple-touch-icon-precomposed" href="/themes/bootstrap/images/logo/favicon.png">
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
        {{-- Vite Assets --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- Theme CSS Assets --}}
        {!! $themeManager->renderCssAssets() !!}
    @endif

    {{-- Livewire Styles --}}
    @livewireStyles

    @stack('styles')
</head>
<body class="body">

    <!-- preload -->
    <div class="preload preload-container">
        <div class="preload-logo">
            <div class="spinner"></div>
            <span class="icon icon-villa-fill"></span>
        </div>
    </div>
    <!-- /preload -->

    <div id="wrapper">
        <div id="pagee" class="clearfix">

            @include($themeManager->getPartial('header'))

            <!-- Main Content -->
            @yield('content')

        </div>
        <!-- /#page -->

    </div>

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
