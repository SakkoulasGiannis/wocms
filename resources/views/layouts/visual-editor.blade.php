<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Visual Editor — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Tailwind Play CDN (JIT): the compiled app.css only contains utility classes
         actually used in the project, so arbitrary classes from pasted HTML
         components (e.g. lg:flex-row, sm:grid-cols-2, bg-white/3, xl:gap-x-20)
         have no CSS to resolve to and the layout collapses. The Play CDN generates
         ANY utility on demand (incl. dynamically inserted blocks), so pasted
         components render exactly like their source while editing. Matches the
         admin layouts which already load this CDN. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('styles')
    <style>
        html, body { height: 100%; overflow: hidden; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased" style="height:100vh; overflow:hidden;">
    {{-- Preload hidden editorjs-field component to trigger its @once @push('scripts') —
         registers all custom tools, lazy loader, autosave, templates, media picker,
         brand tokens BEFORE any user-visible WYSIWYG field is interacted with. --}}
    <div style="display:none" aria-hidden="true">
        <x-editorjs-field name="__preload__" uid="ejs-preload-ve" />
    </div>

    {{ $slot }}
    @stack('scripts')
</body>
</html>
