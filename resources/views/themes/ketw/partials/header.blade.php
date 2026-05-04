@php
    $siteLogo = \App\Models\Setting::get('site_logo');
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $logoUrl = $siteLogo ?: '/themes/kretaeiendom/images/logo/logo@2x.png';
    $phone = \App\Models\Setting::get('site_phone', '');
    $email = \App\Models\Setting::get('site_email', '');
    $headerMenu = app(\App\Services\FrontendMenuService::class)->get('header');
@endphp

<header
    x-data="{ mobileOpen: false, scrolled: false }"
    @scroll.window="scrolled = window.scrollY > 20"
    :class="scrolled ? 'shadow-md bg-white' : 'bg-white/95 backdrop-blur-sm'"
    class="sticky top-0 z-40 w-full transition-shadow"
>
    <div class="mx-auto  px-4 sm:px-6 lg:px-8">
        <div class="flex h-20 items-center justify-between gap-6">

            {{-- Logo --}}
            <div class="flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center">
                    <img
                        src="{{ $logoUrl }}"
                        alt="{{ $siteName }}"
                        class="h-12 w-auto object-contain"
                        width="166"
                        height="48"
                    >
                </a>
            </div>

            {{-- Desktop Navigation --}}
            <nav class="hidden lg:flex lg:flex-1">
                <ul class="flex items-center gap-1">
                    @if($headerMenu)
                        @foreach($headerMenu->rootItems as $item)
                            @php $hasChildren = $item->children->count() > 0; @endphp
                            @php $isCurrent = Request::url() === $item->resolved_url; @endphp

                            <li
                                class="relative"
                                @if($hasChildren)
                                    x-data="{ open: false }"
                                    @mouseenter="open = true"
                                    @mouseleave="open = false"
                                @endif
                            >
                                <a
                                    href="{{ $item->resolved_url }}"
                                    target="{{ $item->target }}"
                                    class="flex items-center gap-1 px-3 py-2 text-sm font-medium transition-colors {{ $isCurrent ? 'text-brand' : 'text-slate-700 hover:text-brand' }}"
                                >
                                    {{ $item->title }}
                                    @if($hasChildren)
                                        <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </a>

                                @if($hasChildren)
                                    {{-- Dropdown (homelengo-style: slide+fade with staggered items) --}}
                                    <div
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-250"
                                        x-transition:enter-start="opacity-0 -translate-y-3"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-2"
                                        class="absolute left-0 top-full min-w-[260px] rounded-2xl border border-slate-200/70 bg-white py-2 shadow-[0_10px_25px_rgba(54,95,104,0.12)]"
                                        style="display:none; margin-top: 8px;"
                                    >
                                        @foreach($item->children as $child)
                                            @php $hasGrandchildren = $child->children->count() > 0; @endphp
                                            <div
                                                class="relative"
                                                @if($hasGrandchildren)
                                                    x-data="{ subOpen: false }"
                                                    @mouseenter="subOpen = true"
                                                    @mouseleave="subOpen = false"
                                                @endif
                                            >
                                                <a
                                                    href="{{ $child->resolved_url }}"
                                                    target="{{ $child->target }}"
                                                    class="group relative flex items-center justify-between py-3 pl-6 pr-4 text-sm font-semibold text-slate-800 transition-all duration-300 ease-out hover:pl-12 hover:text-brand"
                                                >
                                                    <span aria-hidden="true" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 -translate-x-2 opacity-0 transition-all duration-300 ease-out text-brand group-hover:translate-x-0 group-hover:opacity-100">↘</span>
                                                    <span>{{ $child->title }}</span>
                                                    @if($hasGrandchildren)
                                                        <svg class="ml-2 h-3 w-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    @endif
                                                </a>

                                                @if($hasGrandchildren)
                                                    <div
                                                        x-show="subOpen"
                                                        x-transition:enter="transition ease-out duration-250"
                                                        x-transition:enter-start="opacity-0 -translate-x-2"
                                                        x-transition:enter-end="opacity-100 translate-x-0"
                                                        x-transition:leave="transition ease-in duration-150"
                                                        x-transition:leave-start="opacity-100"
                                                        x-transition:leave-end="opacity-0"
                                                        class="absolute left-full top-0 min-w-[220px] rounded-2xl border border-slate-200/70 bg-white py-2 shadow-[0_10px_25px_rgba(54,95,104,0.12)]"
                                                        style="display:none"
                                                    >
                                                        @foreach($child->children as $grandchild)
                                                            <a
                                                                href="{{ $grandchild->resolved_url }}"
                                                                target="{{ $grandchild->target }}"
                                                                class="group relative block py-3 pl-6 pr-4 text-sm font-semibold text-slate-800 transition-all duration-300 ease-out hover:pl-12 hover:text-brand"
                                                            >
                                                                <span aria-hidden="true" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 -translate-x-2 opacity-0 transition-all duration-300 ease-out text-brand group-hover:translate-x-0 group-hover:opacity-100">↘</span>
                                                                {{ $grandchild->title }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    @endif
                </ul>
            </nav>

            {{-- Contact / CTA (Desktop) --}}
            <div class="hidden lg:flex lg:items-center lg:gap-4">
                @if($phone)
                    <a href="tel:{{ $phone }}" class="flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-brand">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        {{ $phone }}
                    </a>
                @endif
                <a
                    href="{{ url('/contact') }}"
                    class="rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-dark"
                >
                    Contact Us
                </a>
            </div>

            {{-- Mobile Menu Toggle --}}
            <button
                type="button"
                @click="mobileOpen = true"
                class="inline-flex items-center justify-center rounded-md p-2 text-slate-700 hover:bg-slate-100 lg:hidden"
                aria-label="Open menu"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile Menu (off-canvas) --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/50 lg:hidden"
        @click="mobileOpen = false"
        style="display:none"
    ></div>

    <aside
        x-show="mobileOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] bg-white shadow-xl lg:hidden"
        style="display:none"
    >
        <div class="flex h-20 items-center justify-between border-b border-slate-200 px-5">
            <a href="{{ url('/') }}">
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain">
            </a>
            <button
                type="button"
                @click="mobileOpen = false"
                class="rounded-md p-2 text-slate-600 hover:bg-slate-100"
                aria-label="Close menu"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="overflow-y-auto px-5 py-4" style="max-height:calc(100vh - 5rem);">
            <ul class="flex flex-col gap-1">
                @if($headerMenu)
                    @foreach($headerMenu->rootItems as $item)
                        @php $hasChildren = $item->children->count() > 0; @endphp
                        <li @if($hasChildren) x-data="{ open: false }" @endif>
                            <div class="flex items-center justify-between">
                                <a
                                    href="{{ $item->resolved_url }}"
                                    target="{{ $item->target }}"
                                    class="flex-1 py-2 text-base font-medium text-slate-800 hover:text-brand"
                                >
                                    {{ $item->title }}
                                </a>
                                @if($hasChildren)
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="p-2 text-slate-600"
                                        :aria-expanded="open"
                                    >
                                        <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            @if($hasChildren)
                                <ul x-show="open" x-transition x-collapse class="ml-4 border-l border-slate-200 pl-3" style="display:none">
                                    @foreach($item->children as $child)
                                        <li>
                                            <a
                                                href="{{ $child->resolved_url }}"
                                                target="{{ $child->target }}"
                                                class="block py-1.5 text-sm text-slate-700 hover:text-brand"
                                            >
                                                {{ $child->title }}
                                            </a>
                                            @if($child->children->count())
                                                <ul class="ml-3 border-l border-slate-200 pl-3">
                                                    @foreach($child->children as $grandchild)
                                                        <li>
                                                            <a
                                                                href="{{ $grandchild->resolved_url }}"
                                                                target="{{ $grandchild->target }}"
                                                                class="block py-1 text-sm text-slate-600 hover:text-brand"
                                                            >
                                                                {{ $grandchild->title }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                @endif
            </ul>

            {{-- Mobile Contact Info --}}
            @if($phone || $email)
                <div class="mt-6 space-y-3 border-t border-slate-200 pt-6">
                    @if($phone)
                        <a href="tel:{{ $phone }}" class="flex items-center gap-3 text-sm text-slate-700">
                            <svg class="h-5 w-5 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            {{ $phone }}
                        </a>
                    @endif
                    @if($email)
                        <a href="mailto:{{ $email }}" class="flex items-center gap-3 text-sm text-slate-700">
                            <svg class="h-5 w-5 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            {{ $email }}
                        </a>
                    @endif
                </div>
            @endif
        </nav>
    </aside>
</header>
