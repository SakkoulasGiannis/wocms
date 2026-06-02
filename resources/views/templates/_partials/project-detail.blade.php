{{-- Shared single-project detail view (Completed Villa / Under Construction / Renovation).
     Expects: $content (the model instance), $title, $template, optionally $listingUrl + $listingLabel. --}}
@php
    $listingUrl = $listingUrl ?? url('/' . ($template->slug ?? ''));
    $listingLabel = $listingLabel ?? ($template->name ?? 'All projects');

    // Resolve main image — Spatie media first, then plain field
    $mainImage = null;
    if ($content && method_exists($content, 'getFirstMediaUrl')) {
        try {
            $mainImage = $content->getFirstMediaUrl('main_image', 'large')
                ?: $content->getFirstMediaUrl('main_image');
        } catch (\Throwable $e) { $mainImage = null; }
    }
    if (! $mainImage && ! empty($content->main_image)) {
        $mainImage = is_string($content->main_image) && str_starts_with($content->main_image, 'http')
            ? $content->main_image
            : asset('storage/' . $content->main_image);
    }
    $mainImage = $mainImage ?: '/themes/kretaeiendom/images/home/house-7.jpg';

    // Gallery normalization: gallery field stores array/JSON of paths or Spatie media collection
    $galleryItems = [];
    if ($content && method_exists($content, 'getMedia')) {
        try {
            $media = $content->getMedia('gallery');
            foreach ($media as $m) {
                $galleryItems[] = [
                    'url' => $m->getFullUrl(),
                    'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getFullUrl('thumb') : $m->getFullUrl(),
                    'name' => $m->name,
                ];
            }
        } catch (\Throwable $e) {}
    }
    if (empty($galleryItems) && ! empty($content->gallery)) {
        $raw = is_string($content->gallery) ? json_decode($content->gallery, true) : $content->gallery;
        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (is_string($item)) {
                    $url = str_starts_with($item, 'http') ? $item : asset('storage/' . $item);
                    $galleryItems[] = ['url' => $url, 'thumb' => $url, 'name' => basename($item)];
                } elseif (is_array($item) && isset($item['url'])) {
                    $galleryItems[] = [
                        'url' => $item['url'],
                        'thumb' => $item['thumb'] ?? $item['url'],
                        'name' => $item['name'] ?? '',
                    ];
                }
            }
        }
    }

    // Size fields are DECIMAL(12,2). Show whole numbers with no decimals and
    // fractional values with up to 2 (trailing zeros trimmed): 120 → "120",
    // 120.50 → "120.5", 1200.05 → "1,200.05". Keeps thousand separators.
    $fmtSize = function ($v) {
        $v = (float) $v;
        return $v == floor($v)
            ? number_format($v, 0)
            : rtrim(rtrim(number_format($v, 2), '0'), '.');
    };

    // Specs
    $specs = [];
    if (! empty($content->location))      { $specs['Location']        = $content->location; }
    if (! empty($content->year_built))    { $specs['Year Built']      = $content->year_built; }
    if (! empty($content->building_size)) { $specs['Building Size']   = $fmtSize($content->building_size) . ' m²'; }
    if (! empty($content->plot_size))     { $specs['Plot Size']       = $fmtSize($content->plot_size) . ' m²'; }
    if (! empty($content->pool_size))     { $specs['Pool Size']       = $fmtSize($content->pool_size) . ' m²'; }
    if (! empty($content->drawn_by))      { $specs['Drawn By']        = $content->drawn_by; }
@endphp

<section class="bg-white">
    {{-- Hero with main image --}}
    <div class="relative h-[44vh] min-h-[320px] w-full overflow-hidden bg-slate-200 sm:h-[60vh]">
        <img src="{{ $mainImage }}" alt="{{ $content->name ?? $title }}" width="1920" height="1080" fetchpriority="high" decoding="async" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-on-surface/70 via-on-surface/15 to-transparent"></div>

        <div class="absolute inset-x-0 bottom-0 px-4 pb-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl text-white">
                <a href="{{ $listingUrl }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 backdrop-blur px-3.5 py-1 text-xs font-semibold uppercase tracking-wider text-white ring-1 ring-white/30 transition-colors hover:bg-white/25">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    {{ $listingLabel }}
                </a>
                <h1 class="mt-4 text-3xl font-extrabold capitalize leading-tight md:text-4xl lg:text-[44px] lg:leading-[1.15]">{{ $content->name ?? $title }}</h1>
                @if(! empty($content->location))
                    <p class="mt-2 inline-flex items-center gap-1.5 text-sm text-white/90 sm:text-base">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                        {{ $content->location }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Body --}}
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            {{-- Specs --}}
            <aside class="lg:col-span-1 lg:order-2">
                <div class="sticky top-32 rounded-2xl bg-surface p-6 shadow-card ring-1 ring-outline">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">Project details</h2>
                    @if(empty($specs))
                        <p class="mt-3 text-sm text-variant-1">No specifications available.</p>
                    @else
                        <dl class="mt-4 divide-y divide-outline">
                            @foreach($specs as $label => $value)
                                <div class="flex items-center justify-between gap-3 py-3 text-sm">
                                    <dt class="font-medium text-variant-1">{{ $label }}</dt>
                                    <dd class="font-bold text-on-surface">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    <a href="{{ url('/contact') }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-dark">
                        Get in touch
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            </aside>

            {{-- Body (rich text) + Gallery --}}
            <div class="lg:col-span-2 lg:order-1">
                @php
                    $bodyHtml = '';
                    if (! empty($content->body)) {
                        try {
                            $bodyHtml = app(\App\Services\EditorJsRenderer::class)->toHtml($content->body);
                        } catch (\Throwable $e) {
                            // Fallback: plain content if it isn't EditorJS JSON
                            $bodyHtml = is_string($content->body) ? $content->body : '';
                        }
                    }
                @endphp
                @if(trim(strip_tags($bodyHtml)) !== '')
                    <div class="mb-12">
                        {!! $bodyHtml !!}
                    </div>
                @endif

                @if(count($galleryItems))
                    <h2 class="text-2xl font-bold text-on-surface md:text-3xl">Gallery</h2>
                    <p class="mt-2 text-sm text-variant-1">{{ count($galleryItems) }} {{ \Illuminate\Support\Str::plural('photo', count($galleryItems)) }}</p>

                    <div
                        x-data="{
                            lightboxOpen: false,
                            current: 0,
                            items: @js($galleryItems),
                            open(i) { this.current = i; this.lightboxOpen = true; document.body.style.overflow = 'hidden'; },
                            close() { this.lightboxOpen = false; document.body.style.overflow = ''; },
                            next() { this.current = (this.current + 1) % this.items.length; },
                            prev() { this.current = (this.current - 1 + this.items.length) % this.items.length; },
                        }"
                        @keydown.window.escape="close()"
                        @keydown.window.arrow-left="if (lightboxOpen) prev()"
                        @keydown.window.arrow-right="if (lightboxOpen) next()"
                        class="mt-6">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach($galleryItems as $i => $g)
                                <button type="button" @click="open({{ $i }})"
                                        class="group relative aspect-square overflow-hidden rounded-xl bg-slate-100 ring-1 ring-outline transition-all hover:ring-brand/40">
                                    <img src="{{ $g['thumb'] }}" alt="{{ $g['name'] }}" width="400" height="400" loading="lazy" decoding="async"
                                         class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                </button>
                            @endforeach
                        </div>

                        {{-- Lightbox --}}
                        <div x-show="lightboxOpen" x-transition.opacity
                             class="fixed inset-0 z-[99999] flex items-center justify-center bg-on-surface/95 p-4"
                             @click.self="close()"
                             style="display:none">
                            <button type="button" @click="close()" class="absolute top-4 right-4 text-white/80 hover:text-white" aria-label="Close">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/></svg>
                            </button>
                            <button type="button" @click.stop="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full bg-white/15 backdrop-blur text-white hover:bg-white/25" aria-label="Previous">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" @click.stop="next()" class="absolute right-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full bg-white/15 backdrop-blur text-white hover:bg-white/25" aria-label="Next">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <img :src="items[current]?.url" :alt="items[current]?.name" class="max-h-[88vh] max-w-[92vw] rounded-xl shadow-2xl">
                            <div class="absolute bottom-6 left-0 right-0 text-center text-sm text-white/80">
                                <span x-text="`${current + 1} / ${items.length}`"></span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl bg-surface p-8 text-center">
                        <p class="text-sm text-variant-1">No gallery images uploaded yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
