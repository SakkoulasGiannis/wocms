@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteLogo = \App\Models\Setting::get('site_logo', '');
    $siteDescription = \App\Models\Setting::get('site_description', 'Specializes in providing high-class real estate services in Crete, Greece.');
    $phone = \App\Models\Setting::get('site_phone', '');
    $email = \App\Models\Setting::get('site_email', '');
    $address = \App\Models\Setting::get('site_address', '');
    $facebook = \App\Models\Setting::get('social_facebook', '');
    $instagram = \App\Models\Setting::get('social_instagram', '');
    $twitter = \App\Models\Setting::get('social_twitter', '');
    $linkedin = \App\Models\Setting::get('social_linkedin', '');
    $youtube = \App\Models\Setting::get('social_youtube', '');

    $footerMenu = \App\Models\Menu::where('location', 'footer')->first();
    $footerItems = $footerMenu ? $footerMenu->items()->whereNull('parent_id')->orderBy('order')->get() : collect();
@endphp

<footer class="bg-slate-900 text-slate-300">
    <div class="mx-auto max-w-8xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-12">

            {{-- Column 1: Brand / Contact --}}
            <div class="lg:col-span-4">
                @if($siteLogo)
                    <a href="{{ url('/') }}" class="inline-block mb-5">
                        <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain brightness-0 invert">
                    </a>
                @else
                    <h3 class="mb-5 text-xl font-bold text-white">{{ $siteName }}</h3>
                @endif

                <p class="text-sm leading-relaxed text-slate-400">{{ $siteDescription }}</p>

                <ul class="mt-6 space-y-3">
                    @if($address)
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-brand-light" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-white">{{ $address }}</span>
                        </li>
                    @endif
                    @if($phone)
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-brand-light" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <a href="tel:{{ $phone }}" class="text-sm text-white hover:text-brand-light">{{ $phone }}</a>
                        </li>
                    @endif
                    @if($email)
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-brand-light" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <a href="mailto:{{ $email }}" class="text-sm text-white hover:text-brand-light">{{ $email }}</a>
                        </li>
                    @endif
                </ul>

                {{-- Social Icons --}}
                @if($facebook || $instagram || $twitter || $linkedin || $youtube)
                    <div class="mt-6 flex items-center gap-3">
                        @if($facebook)
                            <a href="{{ $facebook }}" target="_blank" rel="noopener" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-slate-300 transition-colors hover:bg-brand hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                        @endif
                        @if($instagram)
                            <a href="{{ $instagram }}" target="_blank" rel="noopener" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-slate-300 transition-colors hover:bg-brand hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                        @endif
                        @if($twitter)
                            <a href="{{ $twitter }}" target="_blank" rel="noopener" aria-label="Twitter/X" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-slate-300 transition-colors hover:bg-brand hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        @endif
                        @if($linkedin)
                            <a href="{{ $linkedin }}" target="_blank" rel="noopener" aria-label="LinkedIn" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-slate-300 transition-colors hover:bg-brand hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        @endif
                        @if($youtube)
                            <a href="{{ $youtube }}" target="_blank" rel="noopener" aria-label="YouTube" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-slate-300 transition-colors hover:bg-brand hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Column 2: Quick Links --}}
            <div x-data="{ open: true }" class="lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-base font-semibold text-white">Quick Links</h4>
                    <button type="button" @click="open = !open" class="md:hidden" :aria-expanded="open">
                        <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <ul x-show="open" x-collapse class="mt-4 space-y-2">
                    @if($footerItems->isNotEmpty())
                        @foreach($footerItems as $item)
                            <li>
                                <a
                                    href="{{ $item->resolved_url ?? $item->url }}"
                                    @if($item->target === '_blank') target="_blank" rel="noopener" @endif
                                    class="text-sm text-slate-400 hover:text-brand-light"
                                >
                                    {{ $item->title }}
                                </a>
                            </li>
                        @endforeach
                    @else
                        <li><a href="{{ url('/') }}" class="text-sm text-slate-400 hover:text-brand-light">Home</a></li>
                        <li><a href="{{ url('/properties') }}" class="text-sm text-slate-400 hover:text-brand-light">Properties</a></li>
                        <li><a href="{{ url('/rental-properties') }}" class="text-sm text-slate-400 hover:text-brand-light">Rentals</a></li>
                        <li><a href="{{ url('/blog') }}" class="text-sm text-slate-400 hover:text-brand-light">Blog</a></li>
                        <li><a href="{{ url('/contact') }}" class="text-sm text-slate-400 hover:text-brand-light">Contact</a></li>
                    @endif
                </ul>
            </div>

            {{-- Column 3: Properties --}}
            <div x-data="{ open: true }" class="lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-base font-semibold text-white">Properties</h4>
                    <button type="button" @click="open = !open" class="md:hidden" :aria-expanded="open">
                        <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <ul x-show="open" x-collapse class="mt-4 space-y-2">
                    <li><a href="{{ url('/properties?status=for_sale') }}" class="text-sm text-slate-400 hover:text-brand-light">For Sale</a></li>
                    <li><a href="{{ url('/rental-properties') }}" class="text-sm text-slate-400 hover:text-brand-light">For Rent</a></li>
                    <li><a href="{{ url('/agents') }}" class="text-sm text-slate-400 hover:text-brand-light">Our Agents</a></li>
                    <li><a href="{{ url('/contact') }}" class="text-sm text-slate-400 hover:text-brand-light">Contact Us</a></li>
                </ul>
            </div>

            {{-- Column 4: Newsletter --}}
            <div class="lg:col-span-4">
                <h4 class="text-base font-semibold text-white">Newsletter</h4>
                <p class="mt-4 text-sm text-slate-400">Your weekly/monthly dose of knowledge and inspiration.</p>
                <form
                    action="#"
                    method="post"
                    class="mt-5 flex items-center overflow-hidden rounded-full bg-slate-800 ring-1 ring-slate-700 focus-within:ring-brand"
                >
                    @csrf
                    <input
                        type="email"
                        name="email"
                        required
                        placeholder="Your email address"
                        class="flex-1 bg-transparent px-5 py-3 text-sm text-white placeholder-slate-500 outline-none"
                    >
                    <button
                        type="submit"
                        class="m-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-brand text-white transition-colors hover:bg-brand-dark"
                        aria-label="Subscribe"
                    >
                        <svg class="h-4 w-4" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.00044 9.99935L2.72461 2.60352C8.16867 4.18685 13.3024 6.68806 17.9046 9.99935C13.3027 13.3106 8.16921 15.8118 2.72544 17.3952L5.00044 9.99935ZM5.00044 9.99935H11.2504" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>

        </div>
    </div>

    {{-- Bottom Bar --}}
    <div class="border-t border-slate-800">
        <div class="mx-auto flex max-w-8xl flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} {{ $siteName }}. All Rights Reserved.</p>
            <ul class="flex items-center gap-6">
                <li><a href="{{ url('/terms') }}" class="hover:text-brand-light">Terms Of Services</a></li>
                <li><a href="{{ url('/privacy-policy') }}" class="hover:text-brand-light">Privacy Policy</a></li>
            </ul>
        </div>
    </div>
</footer>
