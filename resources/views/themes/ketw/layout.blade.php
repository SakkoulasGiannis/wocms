@include('themes.ketw.partials.head')
<body class="min-h-screen bg-white text-slate-800 antialiased font-sans">

    {{-- Admin toolbar (only renders for logged-in admins / editors) --}}
    <x-admin-bar />

    @php $themeManager = app(\App\Services\ThemeManager::class); @endphp

    @include($themeManager->getPartial('header'))

    <main>
        @yield('content')
    </main>

    @include($themeManager->getPartial('footer'))

    {{-- Back to top button --}}
    <button
        x-data="{ visible: false }"
        @scroll.window="visible = window.scrollY > 400"
        x-show="visible"
        x-transition
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-6 right-6 z-40 h-12 w-12 rounded-full bg-brand text-white shadow-lg hover:bg-brand-dark transition-colors flex items-center justify-center"
        aria-label="Back to top"
        style="display:none"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Alpine.js (already bundled with Livewire but being explicit) --}}

    {{-- Custom Body Scripts from Settings --}}
    @if(\App\Models\Setting::get('custom_body_scripts'))
        {!! \App\Models\Setting::get('custom_body_scripts') !!}
    @endif

    @livewireScripts
    @stack('scripts')

    {{-- Visual Editor preview integration: when the page is loaded inside the
         visual editor's iframe (?ve=1), clicking ANY section in the rendered
         preview posts a message to the parent so the section's edit panel
         opens automatically. Also handles single-section HTML patches from
         the parent without a full iframe reload. --}}
    @if(request()->has('ve'))
    <style>
        .ve-section {
            cursor: pointer;
            outline: 2px solid transparent;
            outline-offset: 3px;
            transition: outline-color 0.15s;
        }
        .ve-section:hover { outline-color: #c4b5fd !important; }
        .ve-section.ve-active { outline: 2px solid #7c3aed !important; outline-offset: 3px; }
        .ve-section.ve-hidden { opacity: 0.35; outline: 2px dashed #9ca3af !important; }
    </style>
    <script>
        // Click any element → walk up to its section wrapper → tell the parent.
        document.addEventListener('click', function (e) {
            const wrapper = e.target.closest('[data-ve-section-id]');
            if (!wrapper) return;
            e.preventDefault();
            e.stopPropagation();
            const id = parseInt(wrapper.dataset.veSectionId);
            document.querySelectorAll('.ve-section').forEach(w => w.classList.remove('ve-active'));
            wrapper.classList.add('ve-active');
            window.parent.postMessage({ type: 've-section-click', sectionId: id }, '*');
        }, true);

        // Patch a single section's HTML without a full reload.
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
