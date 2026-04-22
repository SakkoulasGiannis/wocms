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

    $variant = strtolower((string) ($content['variant'] ?? 'grid'));
    if (! in_array($variant, ['grid', 'carousel'], true)) {
        $variant = 'grid';
    }

    $isCarousel = $variant === 'carousel';
    $defaultCount = $isCarousel ? 3 : 4;
    $count = (int) ($settings['count'] ?? $content['count'] ?? $defaultCount);
    if ($count < 1) {
        $count = $defaultCount;
    }

    $subtitle = $content['subtitle'] ?? 'Latest News';
    $heading = $content['heading']
        ?? $content['title']
        ?? ($isCarousel ? 'From Our Blog' : 'The Most Recent Estate');
    $description = $content['description'] ?? '';
    $categoryLabel = $content['category_label'] ?? 'Blog';
    $sectionClass = $content['section_class']
        ?? ($isCarousel ? 'py-16 bg-slate-50' : 'py-16 bg-white');

    // Query ContentNode entries for Blog content
    $posts = collect();
    try {
        if (class_exists(\App\Models\ContentNode::class) && class_exists(\App\Models\Blog::class)) {
            $posts = \App\Models\ContentNode::query()
                ->where('content_type', \App\Models\Blog::class)
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->limit($count)
                ->get();
        }
    } catch (\Throwable $e) {
        $posts = collect();
    }

    // Normalize each node into a simple view array
    $normalized = $posts->map(function ($node) use ($categoryLabel) {
        $model = null;
        try {
            $model = $node->getContentModel();
        } catch (\Throwable $e) {
            $model = null;
        }

        $image = null;
        if ($model) {
            if (method_exists($model, 'getFirstMediaUrl')) {
                try {
                    $image = $model->getFirstMediaUrl('featured_image') ?: null;
                } catch (\Throwable $e) {
                    $image = null;
                }
            }
            if (! $image && method_exists($model, 'getFeaturedImageUrl')) {
                try {
                    $image = $model->getFeaturedImageUrl();
                } catch (\Throwable $e) {
                    $image = null;
                }
            }
        }

        $excerpt = '';
        if ($model && ! empty($model->excerpt)) {
            $excerpt = \Illuminate\Support\Str::limit(strip_tags((string) $model->excerpt), 140);
        } elseif ($model && ! empty($model->body)) {
            $excerpt = \Illuminate\Support\Str::limit(strip_tags((string) $model->body), 140);
        }

        $author = ($model && ! empty($model->author)) ? $model->author : 'Admin';
        $dateSource = ($model && ! empty($model->published_at))
            ? $model->published_at
            : $node->created_at;

        try {
            $dateFormatted = $dateSource
                ? \Illuminate\Support\Carbon::parse($dateSource)->format('F d, Y')
                : '';
        } catch (\Throwable $e) {
            $dateFormatted = '';
        }

        return [
            'title' => $node->title ?: ($model->title ?? ''),
            'slug' => $node->slug,
            'link' => url('blog/' . $node->slug),
            'image' => $image ?: '/themes/homelengo/images/blog/blog-20.jpg',
            'excerpt' => $excerpt,
            'date' => $dateFormatted,
            'author' => $author,
            'category' => $categoryLabel,
        ];
    })->values()->all();
@endphp

@if (empty($normalized))
    <section class="{{ $sectionClass }}">
        <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                @if ($subtitle)
                    <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
                @endif
                @if ($heading)
                    <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $heading }}</h2>
                @endif
                <p class="mt-6 text-base text-slate-500">No posts available yet. Check back soon.</p>
            </div>
        </div>
    </section>
@else
    @if ($isCarousel)
        <section
            class="{{ $sectionClass }}"
            x-data="{
                current: 0,
                total: {{ count($normalized) }},
                perView: 3,
                init() {
                    this.updatePerView();
                    window.addEventListener('resize', () => this.updatePerView());
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
                },
                prev() {
                    this.current = this.current <= 0 ? this.maxPage : this.current - 1;
                },
                goTo(i) {
                    this.current = Math.min(i, this.maxPage);
                },
            }"
        >
            <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
                {{-- Header --}}
                <div class="mx-auto max-w-2xl text-center mb-12">
                    @if ($subtitle)
                        <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
                    @endif
                    @if ($heading)
                        <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $heading }}</h2>
                    @endif
                    @if ($description)
                        <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
                    @endif
                </div>

                {{-- Carousel --}}
                <div class="relative">
                    <div class="overflow-hidden">
                        <div
                            class="flex transition-transform duration-500 ease-out"
                            :style="`transform: translateX(-${current * (100 / perView)}%);`"
                        >
                            @foreach ($normalized as $i => $post)
                                <div
                                    class="w-full shrink-0 px-3 md:w-1/2 lg:w-1/3"
                                    wire:key="blog-carousel-{{ $i }}"
                                >
                                    {{-- Blog card --}}
                                    <article class="group flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                                        <a href="{{ $post['link'] }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                                            <img
                                                src="{{ $post['image'] }}"
                                                alt="{{ $post['title'] }}"
                                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                loading="lazy"
                                            >
                                            @if (! empty($post['category']))
                                                <span class="absolute left-4 top-4 inline-flex items-center rounded-full bg-brand px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                                                    {{ $post['category'] }}
                                                </span>
                                            @endif
                                        </a>
                                        <div class="flex flex-1 flex-col p-6">
                                            <h3 class="line-clamp-2 text-lg font-bold text-slate-900 transition-colors group-hover:text-brand">
                                                <a href="{{ $post['link'] }}">{{ $post['title'] }}</a>
                                            </h3>
                                            @if (! empty($post['excerpt']))
                                                <p class="mt-3 line-clamp-3 text-sm text-slate-600">{{ $post['excerpt'] }}</p>
                                            @endif

                                            <div class="mt-5 flex items-center gap-4 border-t border-slate-100 pt-4 text-xs text-slate-500">
                                                @if (! empty($post['date']))
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                        </svg>
                                                        {{ $post['date'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($post['author']))
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                                        </svg>
                                                        {{ $post['author'] }}
                                                    </span>
                                                @endif
                                            </div>

                                            <a href="{{ $post['link'] }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:text-brand-dark transition-colors">
                                                Read more
                                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                </svg>
                                            </a>
                                        </div>
                                    </article>
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
                                class="absolute -left-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-lg ring-1 ring-slate-200 transition-colors hover:bg-brand hover:text-white hover:ring-brand md:-left-5"
                                aria-label="Previous post"
                            >
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="next()"
                                class="absolute -right-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-lg ring-1 ring-slate-200 transition-colors hover:bg-brand hover:text-white hover:ring-brand md:-right-5"
                                aria-label="Next post"
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
                            :class="current === (i - 1) ? 'w-8 bg-brand' : 'w-2.5 bg-slate-300 hover:bg-slate-400'"
                            class="h-2.5 rounded-full transition-all duration-300"
                            :aria-label="`Go to page ${i}`"
                        ></button>
                    </template>
                </div>
            </div>
        </section>
    @else
        <section class="{{ $sectionClass }}">
            <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
                {{-- Header --}}
                <div class="mx-auto max-w-2xl text-center mb-12">
                    @if ($subtitle)
                        <p class="text-sm font-semibold uppercase tracking-widest text-brand">{{ $subtitle }}</p>
                    @endif
                    @if ($heading)
                        <h2 class="mt-3 text-3xl font-bold text-slate-900 md:text-4xl">{{ $heading }}</h2>
                    @endif
                    @if ($description)
                        <p class="mt-4 text-lg text-slate-600">{{ $description }}</p>
                    @endif
                </div>

                {{-- Grid --}}
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($normalized as $i => $post)
                        <article
                            class="group flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                            wire:key="blog-grid-{{ $i }}"
                        >
                            <a href="{{ $post['link'] }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                                <img
                                    src="{{ $post['image'] }}"
                                    alt="{{ $post['title'] }}"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    loading="lazy"
                                >
                                @if (! empty($post['category']))
                                    <span class="absolute left-4 top-4 inline-flex items-center rounded-full bg-brand px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
                                        {{ $post['category'] }}
                                    </span>
                                @endif
                            </a>
                            <div class="flex flex-1 flex-col p-6">
                                <h3 class="line-clamp-2 text-lg font-bold text-slate-900 transition-colors group-hover:text-brand">
                                    <a href="{{ $post['link'] }}">{{ $post['title'] }}</a>
                                </h3>
                                @if (! empty($post['excerpt']))
                                    <p class="mt-3 line-clamp-3 text-sm text-slate-600">{{ $post['excerpt'] }}</p>
                                @endif

                                <div class="mt-5 flex items-center gap-4 border-t border-slate-100 pt-4 text-xs text-slate-500">
                                    @if (! empty($post['date']))
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                            </svg>
                                            {{ $post['date'] }}
                                        </span>
                                    @endif
                                    @if (! empty($post['author']))
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                            </svg>
                                            {{ $post['author'] }}
                                        </span>
                                    @endif
                                </div>

                                <a href="{{ $post['link'] }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:text-brand-dark transition-colors">
                                    Read more
                                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endif
