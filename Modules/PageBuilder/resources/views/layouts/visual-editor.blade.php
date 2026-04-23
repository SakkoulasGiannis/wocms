<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Visual Editor — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        html, body { height: 100%; overflow: hidden; }
        [x-cloak] { display: none !important; }
    </style>

</head>
<body class="bg-gray-100 font-sans antialiased" style="height:100vh; overflow:hidden;">
    {{-- Preload hidden editorjs-field to trigger @once @push('scripts') — ensures ColumnsTool is defined before users interact with WYSIWYG fields --}}
    <div style="display:none" aria-hidden="true">
        <x-editorjs-field name="__preload__" uid="ejs-preload-ve" />
    </div>

    {{ $slot }}
    @stack('scripts')
</body>
</html>
