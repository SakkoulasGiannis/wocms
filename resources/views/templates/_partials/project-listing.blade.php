{{-- Shared listing/grid for Completed Villas / Under Construction / Renovations.
     Expects: $entries (Paginator), $title, $template, optionally $subtitle, $description. --}}
@php
    $subtitle = $subtitle ?? 'Project showcase';
    $description = $description ?? null;
    $entryUrlPrefix = '/' . ($template->slug ?? '');

    // DECIMAL(12,2) size fields: whole → no decimals, fractional → up to 2
    // (trailing zeros trimmed). Same formatter as the project-detail partial.
    $fmtSize = function ($v) {
        $v = (float) $v;
        return $v == floor($v)
            ? number_format($v, 0)
            : rtrim(rtrim(number_format($v, 2), '0'), '.');
    };
@endphp

<section class="py-20 lg:py-24 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Header (homelengo .box-title spec) --}}
        <div class="mx-auto max-w-3xl text-center mb-14">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">{{ $subtitle }}</p>
            <h1 class="mt-4 text-3xl font-extrabold capitalize leading-tight text-on-surface md:text-4xl lg:text-[44px] lg:leading-[1.15]">{{ $title }}</h1>
            @if($description)
                <p class="mt-4 text-lg text-variant-1">{{ $description }}</p>
            @endif
        </div>

        @if($entries->count() === 0)
            <div class="rounded-2xl bg-surface p-12 text-center ring-1 ring-outline">
                <svg class="mx-auto h-12 w-12 text-variant-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-on-surface">No projects yet</h3>
                <p class="mt-2 text-sm text-variant-1">Check back soon for new {{ strtolower($title) }} projects.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($entries as $entry)
                    @php
                        // Resolve main image
                        $img = null;
                        if (method_exists($entry, 'getFirstMediaUrl')) {
                            try { $img = $entry->getFirstMediaUrl('main_image', 'medium') ?: $entry->getFirstMediaUrl('main_image'); } catch (\Throwable $e) {}
                        }
                        if (! $img && ! empty($entry->main_image)) {
                            $img = is_string($entry->main_image) && str_starts_with($entry->main_image, 'http')
                                ? $entry->main_image
                                : asset('storage/' . $entry->main_image);
                        }
                        $img = $img ?: '/themes/kretaeiendom/images/home/house-7.jpg';
                        $detailUrl = $entryUrlPrefix . '/' . ($entry->slug ?? $entry->id);

                        // Gallery count
                        $galleryCount = 0;
                        if (method_exists($entry, 'getMedia')) {
                            try { $galleryCount = $entry->getMedia('gallery')->count(); } catch (\Throwable $e) {}
                        }
                        if ($galleryCount === 0 && ! empty($entry->gallery)) {
                            $raw = is_string($entry->gallery) ? json_decode($entry->gallery, true) : $entry->gallery;
                            $galleryCount = is_array($raw) ? count($raw) : 0;
                        }
                    @endphp
                    <article wire:key="project-{{ $entry->id }}"
                             class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-outline transition-all duration-300 hover:-translate-y-1 hover:shadow-soft hover:ring-brand/30">
                        <a href="{{ $detailUrl }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                            <img src="{{ $img }}" alt="{{ $entry->name ?? $entry->title }}" width="800" height="600" loading="lazy" decoding="async"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">

                            @if(! empty($entry->year_built))
                                <span class="absolute left-4 top-4 inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-on-surface shadow-card">
                                    {{ $entry->year_built }}
                                </span>
                            @endif
                            @if($galleryCount > 0)
                                <span class="absolute right-4 top-4 inline-flex items-center gap-1 rounded-full bg-on-surface/70 backdrop-blur px-2.5 py-1 text-xs font-medium text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.83 0L6 21"/></svg>
                                    {{ $galleryCount }}
                                </span>
                            @endif

                            @if(! empty($entry->location))
                                <div class="absolute bottom-3 left-3 right-3 flex items-center gap-1.5 text-sm text-white drop-shadow">
                                    <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    <span class="truncate">{{ $entry->location }}</span>
                                </div>
                            @endif
                        </a>

                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="line-clamp-1 text-xl font-bold capitalize text-on-surface transition-colors group-hover:text-brand">
                                <a href="{{ $detailUrl }}">{{ $entry->name ?? $entry->title }}</a>
                            </h3>

                            <ul class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                                @if(! empty($entry->building_size))
                                    <li class="flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-variant-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
                                        <span class="text-variant-1">Building:</span>
                                        <span class="font-semibold text-on-surface">{{ $fmtSize($entry->building_size) }} m²</span>
                                    </li>
                                @endif
                                @if(! empty($entry->plot_size))
                                    <li class="flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-variant-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4zM4 9h16M9 4v16"/></svg>
                                        <span class="text-variant-1">Plot:</span>
                                        <span class="font-semibold text-on-surface">{{ $fmtSize($entry->plot_size) }} m²</span>
                                    </li>
                                @endif
                            </ul>

                            <a href="{{ $detailUrl }}" class="mt-auto pt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-on-surface transition-colors hover:text-brand">
                                View Project
                                <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if(method_exists($entries, 'links'))
                <div class="mt-12">
                    {{ $entries->links() }}
                </div>
            @endif
        @endif
    </div>
</section>
