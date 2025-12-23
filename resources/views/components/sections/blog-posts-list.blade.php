@props(['content', 'settings'])

@php
    $count = $settings['count'] ?? 4;
    $columns = $settings['columns'] ?? 2;
    $showExcerpt = $settings['show_excerpt'] ?? true;
    $showDate = $settings['show_date'] ?? true;
    $showAuthor = $settings['show_author'] ?? false;

    // Fetch blog posts (assuming 'blog' template exists)
    $posts = \App\Models\ContentNode::where('content_type', 'App\\Models\\TemplateEntry')
        ->whereHas('entry', function($q) {
            $q->whereHas('template', function($tq) {
                $tq->where('slug', 'like', '%blog%')
                  ->orWhere('slug', 'like', '%article%')
                  ->orWhere('slug', 'like', '%post%');
            });
        })
        ->where('is_published', true)
        ->orderBy('created_at', 'desc')
        ->limit($count)
        ->get();
@endphp

<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']) || !empty($content['subheading']))
            <div class="text-center mb-12">
                @if(!empty($content['subheading']))
                    <p class="text-blue-600 font-semibold mb-2">{{ $content['subheading'] }}</p>
                @endif
                @if(!empty($content['heading']))
                    <h2 class="text-4xl font-bold">{{ $content['heading'] }}</h2>
                @endif
            </div>
        @endif

        @if($posts->count() > 0)
            <div class="grid md:grid-cols-{{ $columns }} gap-8">
                @foreach($posts as $post)
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                        @if($post->entry && isset($post->entry->data['featured_image']))
                            <img src="{{ $post->entry->data['featured_image'] }}" alt="{{ $post->title }}" class="w-full h-48 object-cover">
                        @endif

                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2">
                                <a href="/{{ $post->full_path }}" class="hover:text-blue-600">{{ $post->title }}</a>
                            </h3>

                            @if($showDate || $showAuthor)
                                <div class="text-sm text-gray-500 mb-3">
                                    @if($showDate)
                                        <span>{{ $post->created_at->format('M d, Y') }}</span>
                                    @endif
                                    @if($showAuthor && $post->entry && isset($post->entry->data['author']))
                                        <span class="mx-2">•</span>
                                        <span>{{ $post->entry->data['author'] }}</span>
                                    @endif
                                </div>
                            @endif

                            @if($showExcerpt && $post->entry && isset($post->entry->data['excerpt']))
                                <p class="text-gray-600 mb-4">{{ Str::limit($post->entry->data['excerpt'], 150) }}</p>
                            @endif

                            <a href="/{{ $post->full_path }}" class="text-blue-600 font-semibold hover:underline">
                                Read More →
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="text-center text-gray-500">No blog posts found.</p>
        @endif
    </div>
</section>
