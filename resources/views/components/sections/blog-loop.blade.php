@props(['content' => [], 'settings' => []])

@php
    /* ─────────────────────────────────────────────────────────────────────
       Blog Loop section — pulls blog posts filtered by an optional category
       slug and/or a comma-separated list of tag slugs, then renders them in
       a configurable card grid. Mounted from the visual editor.
       ─────────────────────────────────────────────────────────────────── */
    $heading      = trim((string) ($content['heading'] ?? ''));
    $subheading   = trim((string) ($content['subheading'] ?? ''));
    $limit        = (int) ($content['limit'] ?? 6);
    $categorySlug = trim((string) ($content['category_slug'] ?? ''));
    $tagsCsv      = trim((string) ($content['tags_csv'] ?? ''));
    $orderBy      = $content['order_by'] ?? 'published_at';
    $orderDir     = ($content['order_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    $columns      = (int) ($content['columns'] ?? 3);
    if (! in_array($columns, [1, 2, 3, 4], true)) $columns = 3;
    $gap          = $content['gap'] ?? 'normal';
    $showExcerpt  = filter_var($content['show_excerpt'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $showChips    = filter_var($content['show_chips']   ?? true, FILTER_VALIDATE_BOOLEAN);
    $sectionClass = trim((string) ($content['section_class'] ?? '')) ?: 'py-20 lg:py-24 bg-white';

    $gapClass = match($gap) {
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

    // Query — restrict to published, then apply filters.
    $allowedOrderColumns = ['published_at', 'created_at', 'title'];
    if (! in_array($orderBy, $allowedOrderColumns, true)) {
        $orderBy = 'published_at';
    }

    $query = \App\Models\Blog::query()
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->where('status', 'active')
        ->with(['categories', 'tags']);

    if ($categorySlug !== '') {
        $query->whereHas('categories',
            fn($q) => $q->where('slug', $categorySlug)->orWhere('name', $categorySlug)
        );
    }

    if ($tagsCsv !== '') {
        $tagList = collect(explode(',', $tagsCsv))
            ->map(fn($s) => trim((string) $s))
            ->filter()
            ->values()
            ->all();
        if ($tagList) {
            $query->whereHas('tags',
                fn($q) => $q->whereIn('slug', $tagList)->orWhereIn('name', $tagList)
            );
        }
    }

    $query->orderBy($orderBy, $orderDir);

    if ($limit > 0) {
        $query->limit($limit);
    }

    $posts = $query->get();
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

        @if($posts->isEmpty())
            <div class="rounded-2xl bg-surface p-12 text-center ring-1 ring-outline">
                <p class="text-sm text-variant-1">
                    @if($categorySlug || $tagsCsv)
                        No blog posts match the selected filters.
                    @else
                        No blog posts yet.
                    @endif
                </p>
            </div>
        @else
            <div class="grid {{ $gridClass }} {{ $gapClass }}">
                @foreach($posts as $post)
                    @php
                        $image = $post->getFeaturedImageUrl() ?: '/themes/kretaeiendom/images/home/house-7.jpg';
                        $detailUrl = route('blog.show', $post->slug);
                    @endphp
                    <article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-outline transition-all duration-300 hover:-translate-y-1 hover:shadow-soft hover:ring-brand/30">
                        <a href="{{ $detailUrl }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                            <img src="{{ $image }}"
                                 alt="{{ $post->title }}"
                                 width="800" height="600"
                                 loading="lazy" decoding="async"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        </a>
                        <div class="flex flex-1 flex-col p-5">
                            @if($showChips && ($post->categories->isNotEmpty() || $post->tags->isNotEmpty()))
                                <div class="mb-3 flex flex-wrap gap-1.5">
                                    @foreach($post->categories->take(2) as $cat)
                                        <a href="{{ route('blog.category', $cat->slug) }}"
                                           class="rounded bg-brand/10 px-2 py-0.5 text-xs font-medium text-brand hover:bg-brand/20">{{ $cat->name }}</a>
                                    @endforeach
                                    @foreach($post->tags->take(3) as $tag)
                                        <a href="{{ route('blog.tag', $tag->slug) }}"
                                           class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600 hover:bg-slate-200">#{{ $tag->name }}</a>
                                    @endforeach
                                </div>
                            @endif

                            <h3 class="line-clamp-2 text-xl font-bold capitalize text-on-surface transition-colors group-hover:text-brand">
                                <a href="{{ $detailUrl }}">{{ $post->title }}</a>
                            </h3>

                            @if($showExcerpt && trim((string) $post->excerpt) !== '')
                                <p class="mt-2 line-clamp-3 text-sm text-variant-1">{{ $post->excerpt }}</p>
                            @endif

                            <div class="mt-auto flex items-center justify-between pt-5">
                                @if($post->author)
                                    <span class="text-xs text-variant-1">{{ $post->author }}</span>
                                @endif
                                @if($post->published_at)
                                    <span class="text-xs text-variant-1">{{ \Carbon\Carbon::parse($post->published_at)->format('M j, Y') }}</span>
                                @endif
                            </div>

                            <a href="{{ $detailUrl }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-on-surface transition-colors hover:text-brand">
                                Read more
                                <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
