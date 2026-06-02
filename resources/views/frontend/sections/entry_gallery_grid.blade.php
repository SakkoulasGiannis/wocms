{{-- Entry Gallery — GRID variant.
     Auto-pulls images from the current entry's Spatie media collection.
     Provides a click-to-open lightbox with prev/next + ESC. --}}
@php
    $collection = trim((string) ($content['media_collection'] ?? 'gallery')) ?: 'gallery';
    $heading    = trim((string) ($content['heading'] ?? ''));
    $subheading = trim((string) ($content['subheading'] ?? ''));
    $columns    = (int) ($content['columns'] ?? 3);
    if (! in_array($columns, [2, 3, 4, 5, 6], true)) { $columns = 3; }
    $gapKey     = $content['gap'] ?? 'normal';
    $lightbox   = filter_var($content['lightbox'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $sectionClass = trim((string) ($content['section_class'] ?? '')) ?: 'py-16 lg:py-20 bg-white';

    $gridClass = match($columns) {
        2 => 'grid-cols-1 sm:grid-cols-2',
        4 => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
        5 => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
        6 => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-6',
        default => 'grid-cols-2 sm:grid-cols-3',
    };
    $gapClass = match($gapKey) {
        'tight' => 'gap-2',
        'loose' => 'gap-6',
        default => 'gap-3',
    };

    $items = [];
    if (isset($entry) && is_object($entry) && method_exists($entry, 'getMedia')) {
        try {
            foreach ($entry->getMedia($collection) as $m) {
                $items[] = [
                    'url'   => $m->getFullUrl(),
                    'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getFullUrl('thumb') : $m->getFullUrl(),
                    'name'  => $m->name ?? '',
                ];
            }
        } catch (\Throwable $e) {}
    }
@endphp

<section class="{{ $sectionClass }}">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if($heading !== '' || $subheading !== '')
            <div class="mx-auto max-w-3xl text-center mb-10">
                @if($subheading)
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">{{ $subheading }}</p>
                @endif
                @if($heading)
                    <h2 class="mt-3 text-3xl font-extrabold capitalize leading-tight text-on-surface md:text-4xl">{{ $heading }}</h2>
                @endif
            </div>
        @endif

        @if(empty($items))
            <div class="rounded-2xl bg-surface p-8 text-center text-sm text-variant-1">
                No gallery images uploaded yet.
            </div>
        @else
            <div
                @if($lightbox)
                x-data="{
                    open: false, current: 0,
                    items: @js($items),
                    show(i) { this.current = i; this.open = true; document.body.style.overflow = 'hidden'; },
                    close() { this.open = false; document.body.style.overflow = ''; },
                    next() { this.current = (this.current + 1) % this.items.length; },
                    prev() { this.current = (this.current - 1 + this.items.length) % this.items.length; },
                }"
                @keydown.window.escape="close()"
                @keydown.window.arrow-left="if (open) prev()"
                @keydown.window.arrow-right="if (open) next()"
                @endif>
                <div class="grid {{ $gridClass }} {{ $gapClass }}">
                    @foreach($items as $i => $g)
                        @if($lightbox)
                            <button type="button" @click="show({{ $i }})"
                                    class="group relative aspect-square overflow-hidden rounded-xl bg-slate-100 ring-1 ring-outline transition-all hover:ring-brand/40">
                                <img src="{{ $g['thumb'] }}" alt="{{ $g['name'] }}" width="400" height="400" loading="lazy" decoding="async"
                                     class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            </button>
                        @else
                            <div class="relative aspect-square overflow-hidden rounded-xl bg-slate-100">
                                <img src="{{ $g['thumb'] }}" alt="{{ $g['name'] }}" width="400" height="400" loading="lazy" decoding="async"
                                     class="absolute inset-0 h-full w-full object-cover">
                            </div>
                        @endif
                    @endforeach
                </div>

                @if($lightbox)
                    <div x-show="open" x-transition.opacity
                         class="fixed inset-0 z-[99999] flex items-center justify-center bg-on-surface/95 p-4"
                         @click.self="close()" style="display:none">
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
                @endif
            </div>
        @endif
    </div>
</section>
