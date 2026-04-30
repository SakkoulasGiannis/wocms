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

    @if(\App\Models\Setting::get('ve_tailwind_cdn', false))
        {{-- Tailwind v4 browser CDN — renders Tailwind classes in sections site-wide --}}
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

    @if(request()->has('ve'))
    <style>
        /* wrapper is display:contents — style its first child instead */
        .ve-section-wrapper { display: contents; }
        .ve-section-wrapper > *:first-child {
            cursor: pointer;
            outline: 2px solid transparent;
            outline-offset: 3px;
            transition: outline-color 0.15s;
        }
        .ve-section-wrapper > *:first-child:hover { outline-color: #c4b5fd !important; }
        .ve-section-wrapper.ve-active > *:first-child { outline: 2px solid #7c3aed !important; outline-offset: 3px; }
        .ve-section-wrapper.ve-hidden > *:first-child { opacity: 0.35; outline: 2px dashed #9ca3af !important; }
    </style>
    <script>
        // Event delegation — works for dynamically patched sections too
        document.addEventListener('click', function (e) {
            const wrapper = e.target.closest('[data-ve-section-id]');
            if (!wrapper) return;
            e.preventDefault();
            e.stopPropagation();
            const id = parseInt(wrapper.dataset.veSectionId);
            document.querySelectorAll('.ve-section-wrapper').forEach(w => w.classList.remove('ve-active'));
            wrapper.classList.add('ve-active');
            window.parent.postMessage({ type: 've-section-click', sectionId: id }, '*');
        }, true);

        // Patch a single section's HTML without full reload
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
