@props(['content' => [], 'settings' => []])

@php
    $formSlug = $content['form_slug'] ?? 'contact-form';
    $heading = $content['heading'] ?? '';
    $subheading = $content['subheading'] ?? '';
    $description = $content['description'] ?? '';
    $sectionClass = $content['section_class'] ?? 'py-16';
    $showInfo = (bool) ($content['show_info'] ?? true);
    $showMap = (bool) ($content['show_map'] ?? false);
    $maxWidth = $content['max_width'] ?? 'max-w-3xl';

    // Prefer section content, fall back to site Settings
    $address = $content['address'] ?? \App\Models\Setting::get('site_address');
    $phone = $content['phone'] ?? \App\Models\Setting::get('site_phone', '');
    $email = $content['email'] ?? \App\Models\Setting::get('site_email', '');
    $opentime = $content['opentime'] ?? \App\Models\Setting::get('site_opentime');
    $lat = $content['latitude'] ?? \App\Models\Setting::get('site_latitude', '35.24');
    $lng = $content['longitude'] ?? \App\Models\Setting::get('site_longitude', '24.47');
    $infoHeading = $content['info_heading'] ?? 'Contact Information';
    $facebook = $content['facebook'] ?? \App\Models\Setting::get('social_facebook', '');
    $instagram = $content['instagram'] ?? \App\Models\Setting::get('social_instagram', '');
    $linkedin = $content['linkedin'] ?? \App\Models\Setting::get('social_linkedin', '');
    $twitter = $content['twitter'] ?? \App\Models\Setting::get('social_twitter', '');
    $youtube = $content['youtube'] ?? \App\Models\Setting::get('social_youtube', '');
@endphp

<section class="{{ $sectionClass }}">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        @if($heading || $subheading)
            <div class="mx-auto max-w-2xl text-center mb-10">
                @if($subheading)
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subheading }}</p>
                @endif
                @if($heading)
                    <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $heading }}</h2>
                @endif
                @if($description)
                    <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 gap-10 {{ $showInfo ? 'lg:grid-cols-3' : '' }}">
            {{-- Form --}}
            <div class="{{ $showInfo ? 'lg:col-span-2' : 'mx-auto ' . $maxWidth }}">
                @livewire('frontend.form-renderer', ['slug' => $formSlug])
            </div>

            {{-- Info sidebar --}}
            @if($showInfo)
                <aside class="rounded-2xl bg-slate-50 p-8">
                    <h3 class="text-xl font-bold text-slate-900">{{ $infoHeading }}</h3>
                    <ul class="mt-6 space-y-6 text-sm">
                        @if($address)
                            <li>
                                <div class="flex items-center gap-2 font-semibold text-slate-900">
                                    <svg class="h-4 w-4 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                    Address
                                </div>
                                <p class="mt-1 text-slate-600">{!! nl2br(e($address)) !!}</p>
                            </li>
                        @endif
                        @if($phone || $email)
                            <li>
                                <div class="flex items-center gap-2 font-semibold text-slate-900">
                                    <svg class="h-4 w-4 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg>
                                    Information
                                </div>
                                <p class="mt-1 text-slate-600">
                                    @if($phone)<a href="tel:{{ $phone }}" class="block hover:text-brand">{{ $phone }}</a>@endif
                                    @if($email)<a href="mailto:{{ $email }}" class="block hover:text-brand">{{ $email }}</a>@endif
                                </p>
                            </li>
                        @endif
                        @if($opentime)
                            <li>
                                <div class="flex items-center gap-2 font-semibold text-slate-900">
                                    <svg class="h-4 w-4 text-brand" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                                    Open Hours
                                </div>
                                <p class="mt-1 text-slate-600">{!! nl2br(e($opentime)) !!}</p>
                            </li>
                        @endif

                        @if($facebook || $instagram || $twitter || $linkedin || $youtube)
                            <li>
                                <div class="font-semibold text-slate-900">Follow Us</div>
                                <div class="mt-3 flex items-center gap-2">
                                    @if($facebook)<a href="{{ $facebook }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm transition-colors hover:bg-brand hover:text-white" aria-label="Facebook"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>@endif
                                    @if($instagram)<a href="{{ $instagram }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm transition-colors hover:bg-brand hover:text-white" aria-label="Instagram"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>@endif
                                    @if($twitter)<a href="{{ $twitter }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm transition-colors hover:bg-brand hover:text-white" aria-label="Twitter"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>@endif
                                    @if($linkedin)<a href="{{ $linkedin }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm transition-colors hover:bg-brand hover:text-white" aria-label="LinkedIn"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>@endif
                                    @if($youtube)<a href="{{ $youtube }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm transition-colors hover:bg-brand hover:text-white" aria-label="YouTube"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>@endif
                                </div>
                            </li>
                        @endif
                    </ul>
                </aside>
            @endif
        </div>

        {{-- Map --}}
        @if($showMap)
            <div class="mt-10 overflow-hidden rounded-2xl">
                <div id="contact-map-{{ md5($formSlug) }}" class="h-96 w-full bg-slate-100"></div>
            </div>

            @push('styles')
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            @endpush
            @push('scripts')
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var el = document.getElementById('contact-map-{{ md5($formSlug) }}');
                        if (!el || !window.L) return;
                        var map = L.map(el.id).setView([{{ $lat }}, {{ $lng }}], 14);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);
                        L.marker([{{ $lat }}, {{ $lng }}]).addTo(map);
                    });
                </script>
            @endpush
        @endif
    </div>
</section>
