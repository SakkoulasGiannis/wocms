@props(['content' => [], 'settings' => []])

@php
    /* ─────────────────────────────────────────────────────────────────────
       Entry Loop section — queries entries from any template and renders
       them as a card grid with token-bound fields.
       ─────────────────────────────────────────────────────────────────── */
    $sourceSlug      = $content['source_template'] ?? '';
    $heading         = trim((string) ($content['heading'] ?? ''));
    $subheading      = trim((string) ($content['subheading'] ?? ''));
    $limit           = (int) ($content['limit'] ?? 12);
    $orderBy         = $content['order_by'] ?? 'created_at';
    $orderDir        = ($content['order_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    $showPagination  = filter_var($content['show_pagination'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $perPage         = max(1, (int) ($content['per_page'] ?? 12));
    $columns         = (int) ($content['columns'] ?? 3);
    if (! in_array($columns, [1, 2, 3, 4], true)) $columns = 3;
    $gapKey          = $content['gap'] ?? 'normal';
    $cardImageToken  = $content['card_image_token']    ?? '{main_image:preview}';
    $cardTitleToken  = $content['card_title_token']    ?? '{name}';
    $cardSubtitleTok = $content['card_subtitle_token'] ?? '{location}';
    $cardLinkPattern = $content['card_link_pattern']   ?? '/{template_slug}/{slug}';
    $cardImageFallback = $content['card_image_fallback'] ?? '/themes/kretaeiendom/images/home/house-7.jpg';
    $sectionClass    = $content['section_class']       ?? 'py-20 lg:py-24 bg-white';

    $gapClass = match($gapKey) {
        'tight' => 'gap-4',
        'loose' => 'gap-10',
        default => 'gap-8',
    };
    $gridClass = match($columns) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };

    // Resolve source template
    $sourceTemplate = null;
    if ($sourceSlug) {
        try {
            $sourceTemplate = \App\Models\Template::where('slug', $sourceSlug)->first();
        } catch (\Throwable $e) {}
    }

    $entries = collect();
    $paginator = null;
    if ($sourceTemplate && $sourceTemplate->model_class) {
        $modelClass = str_contains($sourceTemplate->model_class, '\\')
            ? $sourceTemplate->model_class
            : 'App\\Models\\' . $sourceTemplate->model_class;
        try {
            if (class_exists($modelClass)) {
                $table = (new $modelClass)->getTable();
                $orderColumn = \Illuminate\Support\Facades\Schema::hasColumn($table, $orderBy) ? $orderBy : 'created_at';

                $query = $modelClass::query();
                if (method_exists($modelClass, 'scopeActive')) {
                    try { $query->active(); } catch (\Throwable $e) {}
                }
                $query->orderBy($orderColumn, $orderDir);

                if ($showPagination) {
                    $paginator = $query->paginate($perPage);
                    $entries = $paginator->getCollection();
                } else {
                    if ($limit > 0) $query->limit($limit);
                    $entries = $query->get();
                }
            }
        } catch (\Throwable $e) {
            \Log::warning("Entry-loop query failed for {$sourceSlug}: " . $e->getMessage());
        }
    }

    $resolver = app(\App\Services\TokenResolver::class);
@endphp

<section class="{{ $sectionClass }}">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if($heading !== '' || $subheading !== '')
            <div class="mx-auto max-w-3xl text-center mb-12">
                @if($subheading)
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand">{{ $subheading }}</p>
                @endif
                @if($heading)
                    <h2 class="mt-3 text-3xl font-extrabold capitalize leading-tight text-on-surface md:text-4xl lg:text-[44px] lg:leading-[1.15]">{{ $heading }}</h2>
                @endif
            </div>
        @endif

        @if($entries->isEmpty())
            <div class="rounded-2xl bg-surface p-12 text-center ring-1 ring-outline">
                <p class="text-sm text-variant-1">
                    @if(! $sourceTemplate)
                        Pick a source template in the section settings to display entries.
                    @else
                        No entries to show from “{{ $sourceTemplate->name }}” yet.
                    @endif
                </p>
            </div>
        @else
            <div class="grid {{ $gridClass }} {{ $gapClass }}">
                @foreach($entries as $entry)
                    @php
                        // Per-entry token resolution
                        $image    = $resolver->resolve($cardImageToken,    $entry) ?: $cardImageFallback;
                        $title    = $resolver->resolve($cardTitleToken,    $entry);
                        $subtitle = $resolver->resolve($cardSubtitleTok,   $entry);
                        // Link pattern: also substitute {template_slug}
                        $linkRaw  = $resolver->resolve($cardLinkPattern,   $entry);
                        $link     = str_replace('{template_slug}', $sourceTemplate->slug, $linkRaw);
                    @endphp
                    <article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-outline transition-all duration-300 hover:-translate-y-1 hover:shadow-soft hover:ring-brand/30">
                        <a href="{{ $link }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                            <img src="{{ $image }}"
                                 alt="{{ $title }}"
                                 width="800" height="600"
                                 loading="lazy" decoding="async"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        </a>
                        <div class="flex flex-1 flex-col p-5">
                            @if($title)
                                <h3 class="line-clamp-1 text-xl font-bold capitalize text-on-surface transition-colors group-hover:text-brand">
                                    <a href="{{ $link }}">{{ $title }}</a>
                                </h3>
                            @endif
                            @if($subtitle)
                                <p class="mt-1 line-clamp-1 text-sm text-variant-1">{{ $subtitle }}</p>
                            @endif

                            <a href="{{ $link }}" class="mt-auto pt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-on-surface transition-colors hover:text-brand">
                                View
                                <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($showPagination && $paginator && method_exists($paginator, 'links'))
                <div class="mt-12">
                    {{ $paginator->links() }}
                </div>
            @endif
        @endif
    </div>
</section>
