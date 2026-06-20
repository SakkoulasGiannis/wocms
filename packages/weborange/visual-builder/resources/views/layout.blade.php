{{-- Minimal standalone layout — used only when the host doesn't override
     config('visual-builder.layout') with its own admin layout. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('visual-builder.title', 'Visual Builder') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="p-4">
        @yield(config('visual-builder.content_section', 'content'))
    </main>
    @stack('scripts')
</body>
</html>
