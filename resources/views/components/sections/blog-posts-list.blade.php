@props(['content' => [], 'settings' => []])

@php
    $count = (int) ($settings['count'] ?? $content['count'] ?? 6);
    $columns = (int) ($settings['columns'] ?? $content['columns'] ?? 3);
    $showExcerpt = (bool) ($settings['show_excerpt'] ?? true);
    $showDate = (bool) ($settings['show_date'] ?? true);
    $showAuthor = (bool) ($settings['show_author'] ?? false);

    // Fetch blog posts via ContentNode → Blog morphTo
    $posts = \App\Models\ContentNode::where('content_type', 'App\\Models\\Blog')
        ->where('is_published', true)
        ->orderBy('created_at', 'desc')
        ->limit($count)
        ->get();
@endphp

<section class="py-20 lg:py-24 bg-white">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        @if(!empty($content['heading']) || !empty($content['subheading']))
            <div class="text-center mb-12">
                @if(!empty($content['subheading']))
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand mb-2">{{ $content['subheading'] }}</p>
                @endif
                @if(!empty($content['heading']))
                    <h2 class="text-3xl font-bold text-slate-900 md:text-4xl">{{ $content['heading'] }}</h2>
                @endif
            </div>
        @endif

        @if($posts->count() > 0)
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-{{ $columns }}">
                @foreach($posts as $post)
                    @php
                        $blog = $post->content;
                        $imageUrl = $blog && method_exists($blog, 'getFirstMediaUrl') ? $blog->getFirstMediaUrl('featured_image') : '';
                        $excerpt = $blog->excerpt ?? '';
                        $author = $blog->author ?? '';
                        $slug = $post->slug ?? '';
                        $postUrl = $slug ? url("blog/{$slug}") : '#';
                    @endphp
                    <article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-xl">
                        <a href="{{ $postUrl }}" class="block aspect-16/9 overflow-hidden bg-slate-100">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                            @else
                                <div class="h-full w-full bg-gradient-to-br from-brand to-brand-dark"></div>
                            @endif
                        </a>

                        <div class="p-6">
                            @if($showDate || ($showAuthor && $author))
                                <div class="flex items-center gap-3 text-xs text-slate-500 mb-3">
                                    @if($showDate)
                                        <span>{{ $post->created_at->format('M d, Y') }}</span>
                                    @endif
                                    @if($showAuthor && $author)
                                        <span>&bull;</span>
                                        <span>{{ $author }}</span>
                                    @endif
                                </div>
                            @endif

                            <h3 class="text-lg font-semibold text-slate-900 group-hover:text-brand line-clamp-2">
                                <a href="{{ $postUrl }}">{{ $post->title }}</a>
                            </h3>

                            @if($showExcerpt && $excerpt)
                                <p class="mt-2 text-sm text-slate-600 line-clamp-3">{{ Str::limit($excerpt, 150) }}</p>
                            @endif

                            <a href="{{ $postUrl }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand hover:text-brand-dark">
                                Read more
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="text-center text-slate-500">No blog posts found.</p>
        @endif
    </div>
</section>
