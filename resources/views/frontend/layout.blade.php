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

    {{-- Vite Assets (CSS & JS from npm run build) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tailwind Plus Elements --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>

    @stack('styles')
</head>
<body class="bg-gray-50">
    @include('frontend.partials.header')

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    @include('frontend.partials.footer')

    @stack('scripts')
</body>
</html>
