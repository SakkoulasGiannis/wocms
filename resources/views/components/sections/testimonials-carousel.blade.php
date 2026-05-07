@props(['content' => [], 'settings' => []])

@php
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

    $sectionClass = $content['section_class'] ?? 'bg-surface';
    $subtitle = $content['subtitle'] ?? 'Our Testimonials';
    $heading = $content['heading'] ?? $content['title'] ?? 'What people say';
    $description = $content['description'] ?? 'Our seasoned team excels in real estate with years of successful market navigation, offering informed decisions and optimal results.';

    $items = $ensureArray($content['items'] ?? $content['testimonials'] ?? null, [
        [
            'name' => 'Courtney Henry',
            'title' => 'CEO Themesflat',
            'quote' => 'My experience with property management services has exceeded expectations. They efficiently manage properties with a professional and attentive approach in every situation.',
            'rating' => 5,
            'avatar' => '/themes/kretaeiendom/images/avatar/avt-png1.png',
        ],
        [
            'name' => 'Esther Howard',
            'title' => 'CEO Themesflat',
            'quote' => 'The team delivered on every promise. Communication was clear, timelines were met, and the end result was far beyond what I expected. Truly a standout experience.',
            'rating' => 5,
            'avatar' => '/themes/kretaeiendom/images/avatar/avt-png2.png',
        ],
        [
            'name' => 'Annette Black',
            'title' => 'CEO Themesflat',
            'quote' => 'Professional, responsive, and thorough. Every interaction reinforced my confidence in their expertise. I would absolutely recommend them to anyone.',
            'rating' => 5,
            'avatar' => '/themes/kretaeiendom/images/avatar/avt-png4.png',
        ],
    ]);

    // Normalize keys: support both our schema and legacy testimonial schema
    $items = array_map(function ($t) {
        return [
            'name' => $t['name'] ?? '',
            'title' => $t['title'] ?? $t['role'] ?? '',
            'quote' => $t['quote'] ?? $t['text'] ?? '',
            'rating' => (int) ($t['rating'] ?? $t['stars'] ?? 5),
            'avatar' => $t['avatar'] ?? '/themes/kretaeiendom/images/avatar/avt-png1.png',
        ];
    }, $items);

    $total = count($items);
@endphp

@if ($total > 0)
    <section
        class="py-20 lg:py-24 {{ $sectionClass }}"
        x-data="{
            current: 0,
            total: {{ $total }},
            perView: 3,
            autoplay: true,
            timer: null,
            init() {
                this.updatePerView();
                window.addEventListener('resize', () => this.updatePerView());
                if (this.autoplay && this.total > this.perView) {
                    this.startAutoplay();
                }
            },
            updatePerView() {
                if (window.innerWidth < 768) {
                    this.perView = 1;
                } else if (window.innerWidth < 1024) {
                    this.perView = 2;
                } else {
                    this.perView = 3;
                }
                const maxPage = Math.max(0, this.total - this.perView);
                if (this.current > maxPage) {
                    this.current = maxPage;
                }
            },
            get maxPage() {
                return Math.max(0, this.total - this.perView);
            },
            next() {
                this.current = this.current >= this.maxPage ? 0 : this.current + 1;
                this.restartAutoplay();
            },
            prev() {
                this.current = this.current <= 0 ? this.maxPage : this.current - 1;
                this.restartAutoplay();
            },
            goTo(i) {
                this.current = Math.min(i, this.maxPage);
                this.restartAutoplay();
            },
            startAutoplay() {
                this.timer = setInterval(() => this.next(), 5000);
            },
            restartAutoplay() {
                clearInterval(this.timer);
                if (this.autoplay && this.total > this.perView) {
                    this.startAutoplay();
                }
            },
        }"
        @mouseenter="clearInterval(timer)"
        @mouseleave="if (autoplay && total > perView) startAutoplay()"
    >
        <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
            {{-- Header (homelengo .box-title spec) --}}
            <div class="mx-auto max-w-2xl text-center mb-14">
                @if ($subtitle)
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">{{ $subtitle }}</p>
                @endif
                @if ($heading)
                    <h2 class="mt-4 text-3xl font-extrabold capitalize leading-tight text-on-surface md:text-4xl lg:text-[44px] lg:leading-[1.15]">{{ $heading }}</h2>
                @endif
                @if ($description)
                    <p class="mt-4 text-lg text-variant-1">{{ $description }}</p>
                @endif
            </div>

            {{-- Carousel --}}
            <div class="relative">
                <div class="overflow-hidden">
                    <div
                        class="flex transition-transform duration-500 ease-out"
                        :style="`transform: translateX(-${current * (100 / perView)}%);`"
                    >
                        @foreach ($items as $i => $t)
                            <div
                                class="w-full shrink-0 px-3 md:w-1/2 lg:w-1/3"
                                wire:key="testimonial-{{ $i }}"
                            >
                                <div class="group relative h-full rounded-2xl bg-white p-8 shadow-card ring-1 ring-outline transition-all duration-300 hover:-translate-y-1 hover:shadow-soft hover:ring-brand/30">
                                    {{-- Quote icon (homelengo .icon-quote, brand color) --}}
                                    <svg class="h-12 w-12 text-brand" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M7.17 6C4.32 6 2 8.32 2 11.17v6.83h6.83v-6.83H5.17c0-1.75 1.42-3.17 3.17-3.17V6zm10 0c-2.85 0-5.17 2.32-5.17 5.17v6.83h6.83v-6.83h-3.66c0-1.75 1.42-3.17 3.17-3.17V6z"/>
                                    </svg>

                                    {{-- Stars --}}
                                    <div class="mt-5 flex items-center gap-1">
                                        @for ($s = 0; $s < 5; $s++)
                                            <svg class="h-4 w-4 {{ $s < $t['rating'] ? 'text-amber-400' : 'text-outline' }}" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.966a1 1 0 00.95.69h4.17c.969 0 1.371 1.24.588 1.81l-3.375 2.453a1 1 0 00-.363 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.376-2.453a1 1 0 00-1.175 0l-3.375 2.453c-.784.57-1.838-.196-1.539-1.118l1.287-3.966a1 1 0 00-.363-1.118L2.05 9.393c-.783-.57-.38-1.81.588-1.81h4.17a1 1 0 00.95-.69l1.286-3.966z"/>
                                            </svg>
                                        @endfor
                                    </div>

                                    {{-- Quote --}}
                                    <p class="mt-5 text-base leading-relaxed text-variant-1">
                                        &ldquo;{{ $t['quote'] }}&rdquo;
                                    </p>

                                    {{-- Author --}}
                                    <div class="mt-6 flex items-center gap-4 border-t border-outline pt-5">
                                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-full bg-brand-soft ring-2 ring-brand/20">
                                            <img src="{{ $t['avatar'] }}" alt="{{ $t['name'] }}" class="h-full w-full object-cover">
                                        </div>
                                        <div>
                                            <h6 class="text-base font-bold capitalize text-on-surface">{{ $t['name'] }}</h6>
                                            @if ($t['title'])
                                                <p class="text-sm text-variant-1">{{ $t['title'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Arrows --}}
                <template x-if="total > perView">
                    <div>
                        <button
                            type="button"
                            @click="prev()"
                            class="absolute -left-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-on-surface shadow-card ring-1 ring-outline transition-colors hover:bg-brand hover:text-white hover:ring-brand md:-left-5"
                            aria-label="Previous testimonial"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="next()"
                            class="absolute -right-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-on-surface shadow-card ring-1 ring-outline transition-colors hover:bg-brand hover:text-white hover:ring-brand md:-right-5"
                            aria-label="Next testimonial"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Dots --}}
            <div class="mt-8 flex items-center justify-center gap-2" x-show="total > perView">
                <template x-for="i in (maxPage + 1)" :key="i">
                    <button
                        type="button"
                        @click="goTo(i - 1)"
                        :class="current === (i - 1) ? 'w-8 bg-brand' : 'w-2.5 bg-outline hover:bg-variant-2'"
                        class="h-2.5 rounded-full transition-all duration-300"
                        :aria-label="`Go to page ${i}`"
                    ></button>
                </template>
            </div>
        </div>
    </section>
@endif
