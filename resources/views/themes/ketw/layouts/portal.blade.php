{{--
    Portal layout — same <head> as the site, but a DIFFERENT header + footer
    and its own menu (slug 'portal'). Assigned per-node via the `layout`
    column (slug "portal"). Children of a portal-rooted page inherit it.

    Anatomy (clone this to build any custom layout):
      1. @include the shared head partial (keeps SEO/CSS/fonts consistent)
      2. include YOUR header partial (here: header-portal — calls its own menu)
      3. @yield('content')  ← the page's sections render here
      4. include YOUR footer partial (here: footer-portal)
--}}
@include('themes.ketw.partials.head')
<body class="min-h-screen bg-white text-slate-800 antialiased font-sans">

    {{-- Admin toolbar still renders for logged-in admins / editors --}}
    <x-admin-bar />

    @include('themes.ketw.partials.header-portal')

    <main>
        @yield('content')
    </main>

    @include('themes.ketw.partials.footer-portal')

    {{-- Custom Body Scripts from Settings --}}
    @if(\App\Models\Setting::get('custom_body_scripts'))
        {!! \App\Models\Setting::get('custom_body_scripts') !!}
    @endif

    @livewireScripts
    @stack('scripts')
</body>
</html>
