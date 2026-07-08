<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    @include('partials.favicon')

    {{-- Analytics & tracking (Settings → Integrations) --}}
    @include('partials.analytics')

    {{-- SEO Meta Tags --}}
    @if(isset($content) || isset($post))
        <x-seo-meta :entry="$content ?? $post ?? null" :title="$content->title ?? $post->title ?? $title ?? ''" />
    @else
        <title>@yield('title', $title ?? config('app.name'))</title>
        <meta name="description" content="@yield('description', '')">
    @endif

    {{-- Vite Assets (CSS & JS from npm run build) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tailwind Plus Elements removed: no <el-*> component is used on the frontend,
         it was ~492 KiB of unused JS (Lighthouse). Re-add per-page if ever needed. --}}

    {{-- Livewire Styles --}}
    @livewireStyles

    @stack('styles')
</head>
<body class="bg-gray-50">
    @include('frontend.partials.header')

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    @include('frontend.partials.footer')

    {{-- Livewire Scripts --}}
    @livewireScripts

    @stack('scripts')
</body>
</html>
