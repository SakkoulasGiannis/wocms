{{--
    Minimal layout — same <head> as the default, but NO site header / footer
    chrome. For landing pages, embeds, or sub-sections that supply their own
    navigation. Selected per-node via the `layout` column (slug "minimal").
--}}
@include('themes.ketw.partials.head')
<body class="min-h-screen bg-white text-slate-800 antialiased font-sans">

    {{-- Admin toolbar still renders for logged-in admins / editors --}}
    <x-admin-bar />

    <main>
        @yield('content')
    </main>

    {{-- Custom Body Scripts from Settings --}}
    @if(\App\Models\Setting::get('custom_body_scripts'))
        {!! \App\Models\Setting::get('custom_body_scripts') !!}
    @endif

    @livewireScripts
    @stack('scripts')
</body>
</html>
