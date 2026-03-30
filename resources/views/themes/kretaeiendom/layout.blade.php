<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    @if(\App\Models\Setting::get('site_favicon'))
        <link rel="shortcut icon" href="{{ \App\Models\Setting::get('site_favicon') }}">
        <link rel="apple-touch-icon-precomposed" href="{{ \App\Models\Setting::get('site_favicon') }}">
    @else
        <link rel="shortcut icon" href="/themes/kretaeiendom/images/logo/favicon.png">
    @endif

    {{-- SEO Meta Tags --}}
    @if(isset($content) || isset($post))
        <x-seo-meta :entry="$content ?? $post ?? null" :title="$content->title ?? $post->title ?? $title ?? ''" />
    @else
        <title>@yield('title', $title ?? \App\Models\Setting::get('site_name', config('app.name')))</title>
        <meta name="description" content="@yield('description', '')">
    @endif

    {{-- Theme Assets --}}
    @php $themeManager = app(\App\Services\ThemeManager::class); @endphp
    {!! $themeManager->renderCssAssets() !!}

    {{-- Custom Head Scripts from Settings --}}
    @if(\App\Models\Setting::get('custom_head_scripts'))
        {!! \App\Models\Setting::get('custom_head_scripts') !!}
    @endif

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

    <div id="wrapper">
        <div id="pagee" class="clearfix">

            @include($themeManager->getPartial('header'))

            @yield('content')

        </div>
    </div>

    @include($themeManager->getPartial('footer'))

    {{-- Theme JS Assets --}}
    {!! $themeManager->renderJsAssets() !!}

    {{-- Parent links clickable on desktop --}}
    <script>
    (function(){
        function enableParentLinksOnDesktop(){
            try{
                if (window.matchMedia('(min-width: 992px)').matches && window.jQuery) {
                    var $ = window.jQuery;
                    $('.navigation li.dropdown2 > a').off('click');
                    $('.main-header .navigation li.dropdown2 > a').off('click');
                }
            }catch(e){}
        }
        if (document.readyState !== 'loading') enableParentLinksOnDesktop();
        else document.addEventListener('DOMContentLoaded', enableParentLinksOnDesktop);
        window.addEventListener('resize', enableParentLinksOnDesktop);
    })();
    </script>

    {{-- Custom Body Scripts from Settings --}}
    @if(\App\Models\Setting::get('custom_body_scripts'))
        {!! \App\Models\Setting::get('custom_body_scripts') !!}
    @endif

    @livewireScripts
    @stack('scripts')
</body>
</html>
