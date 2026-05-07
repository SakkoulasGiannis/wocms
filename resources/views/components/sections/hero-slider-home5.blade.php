@props(['content' => [], 'settings' => []])

@php
    // Defensive helper: ensure value is array (JSON strings may come from form/editor)
    $ensureArray = function ($val, $default = []) {
        if (is_array($val)) {
            return $val;
        }
        if (is_string($val) && $val !== '') {
            $d = json_decode($val, true);
            if (is_array($d)) {
                return $d;
            }
        }
        return $default;
    };

    $sectionClass = $content['section_class'] ?? '';

    // Fallbacks when no slider is selected
    $defaultHeading = $content['heading'] ?? 'Indulge in Your';
    $animatedWords = $ensureArray($content['animated_words'] ?? null, ['Sanctuary', 'Safe House']);
    $defaultSubtitle = $content['subtitle'] ?? 'Discover your private oasis, where every corner, from the spacious garden to the relaxing pool, is crafted for your comfort and enjoyment.';
    $categories = $ensureArray($content['categories'] ?? null, [
        ['icon' => 'home', 'label' => 'Houses', 'url' => '#'],
        ['icon' => 'villa', 'label' => 'Villa', 'url' => '#'],
        ['icon' => 'office', 'label' => 'Office', 'url' => '#'],
        ['icon' => 'apartment', 'label' => 'Apartments', 'url' => '#'],
    ]);

    // Autoplay settings
    $autoplay = (bool) ($settings['autoplay'] ?? $content['autoplay'] ?? true);
    $interval = (int) ($settings['interval'] ?? $content['interval'] ?? 5000);

    // Try to load slides from Slider module
    $sliderSlug = $content['slider_slug'] ?? null;
    $sliderId = $content['slider_id'] ?? null;
    $sliderSlides = collect();

    if ($sliderSlug || $sliderId) {
        try {
            $sliderModel = $sliderId
                ? \Modules\Slider\Models\Slider::with(['slides' => fn ($q) => $q->where('is_active', true)->orderBy('order')])->where('is_active', true)->find($sliderId)
                : \Modules\Slider\Models\Slider::with(['slides' => fn ($q) => $q->where('is_active', true)->orderBy('order')])->where('slug', $sliderSlug)->where('is_active', true)->first();

            if ($sliderModel) {
                $sliderSlides = $sliderModel->slides;
            }
        } catch (\Throwable $e) {
        }
    }

    $useSliderData = $sliderSlides->isNotEmpty();

    if ($useSliderData) {
        $bgSlides = $sliderSlides->map(fn ($s) => [
            'url' => $s->getFirstMediaUrl('image') ?: '/themes/kretaeiendom/images/slider/slider-5.jpg',
            'media_type' => $s->media_type ?? 'image',
            'video_url' => $s->video_url,
            'video_file_url' => $s->getFirstMediaUrl('video'),
            'title' => $s->title,
            'subtitle' => $s->subtitle ?? null,
            'description' => $s->description,
            'link' => $s->link,
            'button_text' => $s->button_text,
        ])->toArray();
        $thumbSlides = $sliderSlides->map(fn ($s) => $s->getFirstMediaUrl('image', 'thumb') ?: $s->getFirstMediaUrl('image') ?: '/themes/kretaeiendom/images/slider/slider-pagi.jpg')->toArray();
    } else {
        $defaultImages = $ensureArray($content['bg_slides'] ?? null, [
            '/themes/kretaeiendom/images/slider/slider-5.jpg',
            '/themes/kretaeiendom/images/slider/slider-5-1.jpg',
            '/themes/kretaeiendom/images/slider/slider-5-2.jpg',
            '/themes/kretaeiendom/images/slider/slider-5-3.jpg',
        ]);
        $bgSlides = array_map(fn ($url) => [
            'url' => $url,
            'media_type' => 'image',
            'video_url' => null,
            'video_file_url' => null,
            'title' => null,
            'subtitle' => null,
            'description' => null,
            'link' => null,
            'button_text' => null,
        ], $defaultImages);
        $thumbSlides = $ensureArray($content['thumb_slides'] ?? null, [
            '/themes/kretaeiendom/images/slider/slider-pagi.jpg',
            '/themes/kretaeiendom/images/slider/slider-pagi2.jpg',
            '/themes/kretaeiendom/images/slider/slider-pagi3.jpg',
            '/themes/kretaeiendom/images/slider/slider-pagi4.jpg',
        ]);
    }

    // Normalize thumbSlides: entries may be strings or arrays with a 'url' key
    $thumbSlides = array_map(fn ($t) => is_array($t) ? ($t['url'] ?? '') : $t, $thumbSlides);

    // Category icon SVGs (lucide/heroicon-ish paths)
    $catIcons = [
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>',
        'villa' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 21V10l9-6 9 6v11M9 21v-6h6v6"/>',
        'office' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>',
        'apartment' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 21V5a2 2 0 012-2h12a2 2 0 012 2v16M8 7h2m4 0h2M8 11h2m4 0h2M8 15h2m4 0h2M10 21v-4h4v4"/>',
    ];
    $totalSlides = count($bgSlides);
@endphp

@if ($totalSlides > 0)
    <section
        class="relative h-screen min-h-[640px] overflow-hidden bg-slate-900 {{ $sectionClass }}"
        x-data="{
            current: 0,
            total: {{ $totalSlides }},
            autoplay: {{ $autoplay ? 'true' : 'false' }},
            interval: {{ $interval }},
            timer: null,
            rotatingIndex: 0,
            rotatingWords: @js($animatedWords),
            rotatingTimer: null,
            init() {
                if (this.autoplay && this.total > 1) {
                    this.startAutoplay();
                }
                if (this.rotatingWords.length > 1) {
                    this.rotatingTimer = setInterval(() => {
                        this.rotatingIndex = (this.rotatingIndex + 1) % this.rotatingWords.length;
                    }, 2200);
                }
            },
            next() {
                this.current = (this.current + 1) % this.total;
                this.restartAutoplay();
            },
            prev() {
                this.current = (this.current - 1 + this.total) % this.total;
                this.restartAutoplay();
            },
            goTo(i) {
                this.current = i;
                this.restartAutoplay();
            },
            startAutoplay() {
                this.timer = setInterval(() => this.next(), this.interval);
            },
            restartAutoplay() {
                clearInterval(this.timer);
                if (this.autoplay && this.total > 1) {
                    this.startAutoplay();
                }
            },
        }"
        @mouseenter="clearInterval(timer)"
        @mouseleave="if (autoplay && total > 1) startAutoplay()"
    >
        {{-- Background image slides (full-screen) --}}
        @foreach ($bgSlides as $i => $slide)
            <div
                class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                :class="current === {{ $i }} ? 'opacity-100 z-0' : 'opacity-0 z-0'"
            >
                @if (($slide['media_type'] ?? 'image') === 'youtube' && $slide['video_url'])
                    @php
                        preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $slide['video_url'], $ytMatch);
                        $ytId = $ytMatch[1] ?? null;
                    @endphp
                    @if ($ytId)
                        <div class="relative h-full w-full overflow-hidden">
                            <iframe
                                src="https://www.youtube.com/embed/{{ $ytId }}?autoplay=1&mute=1&controls=0&loop=1&playlist={{ $ytId }}&rel=0"
                                class="pointer-events-none absolute left-1/2 top-1/2 h-[120%] w-[120%] -translate-x-1/2 -translate-y-1/2 border-0"
                                allow="autoplay;encrypted-media" allowfullscreen
                            ></iframe>
                            @if ($slide['url'] ?? null)
                                <img src="{{ $slide['url'] }}" alt="poster" class="absolute inset-0 -z-10 h-full w-full object-cover">
                            @endif
                        </div>
                    @endif
                @elseif (($slide['media_type'] ?? 'image') === 'video' && ($slide['video_file_url'] ?? null))
                    <video autoplay muted loop playsinline class="h-full w-full object-cover"
                        @if ($slide['url'] ?? null) poster="{{ $slide['url'] }}" @endif>
                        <source src="{{ $slide['video_file_url'] }}" type="video/mp4">
                    </video>
                @else
                    <img src="{{ $slide['url'] ?? '' }}" alt="{{ $slide['title'] ?? 'slider' }}" class="h-full w-full object-cover">
                @endif
            </div>
        @endforeach

        {{-- (No dark overlay — keep the slider image clean per design preference.
              If text contrast becomes an issue, consider per-block text-shadow
              instead of darkening the whole image.) --}}

        {{-- Content grid: left content + right thumbnails --}}
        <div class="relative z-20 mx-auto flex h-full max-w-8xl items-center px-4 sm:px-6 lg:px-8">
            <div class="grid w-full grid-cols-1 items-center gap-10 lg:grid-cols-2">
                {{-- Left: content --}}
                <div class="text-white">
                    @if ($useSliderData)
                        {{-- Per-slide content --}}
                        @foreach ($bgSlides as $i => $slide)
                            <div
                                x-show="current === {{ $i }}"
                                x-transition:enter="transition ease-out duration-500 delay-150"
                                x-transition:enter-start="opacity-0 translate-y-4"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                @if (! $loop->first) style="display:none" @endif
                            >
                                @if (! empty($slide['subtitle']))
                                    <p class="mb-4 text-sm font-semibold uppercase tracking-widest text-brand-light">
                                        {{ $slide['subtitle'] }}
                                    </p>
                                @endif
                                @if (! empty($slide['title']))
                                    <h1 class="text-4xl font-bold leading-tight md:text-5xl lg:text-6xl">
                                        {{ $slide['title'] }}
                                    </h1>
                                @endif
                                @if (! empty($slide['description']))
                                    <p class="mt-6 max-w-xl text-base text-white/80 md:text-lg">
                                        {{ $slide['description'] }}
                                    </p>
                                @endif
                                @if (! empty($slide['link']))
                                    <div class="mt-8">
                                        <a href="{{ $slide['link'] }}"
                                            class="inline-flex items-center gap-2 rounded-full bg-brand px-7 py-3 text-sm font-semibold text-white shadow-lg transition-colors hover:bg-brand-dark">
                                            {{ $slide['button_text'] ?? 'Learn More' }}
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        {{-- Static content with animated rotating words --}}
                        <h1 class="text-4xl font-bold leading-tight md:text-5xl lg:text-6xl">
                            {{ $defaultHeading }}
                            <br>
                            <span class="relative inline-block text-brand-light">
                                @foreach ($animatedWords as $i => $word)
                                    <span
                                        x-show="rotatingIndex === {{ $i }}"
                                        x-transition:enter="transition ease-out duration-500"
                                        x-transition:enter-start="opacity-0 translate-y-4"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-200 absolute inset-0"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-4"
                                        @if (! $loop->first) style="display:none" @endif
                                    >{{ $word }}</span>
                                @endforeach
                            </span>
                        </h1>
                        <p class="mt-6 max-w-xl text-base text-white/80 md:text-lg">
                            {{ $defaultSubtitle }}
                        </p>
                        @if (! empty($categories))
                            <div class="mt-8 flex flex-wrap gap-3">
                                @foreach ($categories as $cat)
                                    <a href="{{ $cat['url'] ?? '#' }}"
                                        class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-2.5 text-sm font-medium text-white backdrop-blur-sm ring-1 ring-white/20 transition-colors hover:bg-white hover:text-brand">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            {!! $catIcons[$cat['icon'] ?? 'home'] ?? $catIcons['home'] !!}
                                        </svg>
                                        {{ $cat['label'] ?? '' }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Right: thumbnail pagination — homelengo home-5 style:
                     vertical column, right-aligned, 80x80 squares, rounded-2xl,
                     inactive opacity 70%. --}}
                @if ($totalSlides > 1)
                    <div class="hidden lg:flex lg:justify-end">
                        <div class="flex flex-col items-end gap-2.5">
                            @foreach ($thumbSlides as $i => $thumb)
                                <button
                                    type="button"
                                    @click="goTo({{ $i }})"
                                    :class="current === {{ $i }} ? 'opacity-100 ring-2 ring-white' : 'opacity-70 hover:opacity-100'"
                                    class="group relative h-20 w-20 overflow-hidden rounded-2xl transition-all duration-300 ease-out cursor-pointer"
                                    aria-label="Go to slide {{ $i + 1 }}"
                                >
                                    <img src="{{ $thumb }}" alt="thumbnail {{ $i + 1 }}"
                                         class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Mobile pagination dots --}}
        @if ($totalSlides > 1)
            <div class="absolute bottom-6 left-1/2 z-30 flex -translate-x-1/2 items-center gap-2.5 lg:hidden">
                @foreach ($bgSlides as $i => $s)
                    <button
                        type="button"
                        @click="goTo({{ $i }})"
                        :class="current === {{ $i }} ? 'w-8 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/70'"
                        class="h-2.5 rounded-full transition-all duration-300"
                        aria-label="Go to slide {{ $i + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif

        {{-- Arrows --}}
        @if ($totalSlides > 1)
            <button
                type="button"
                @click="prev()"
                class="absolute left-4 top-1/2 z-30 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-brand hover:text-white"
                aria-label="Previous slide"
            >
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button
                type="button"
                @click="next()"
                class="absolute right-4 top-1/2 z-30 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-brand hover:text-white"
                aria-label="Next slide"
            >
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        @endif
    </section>
@endif
