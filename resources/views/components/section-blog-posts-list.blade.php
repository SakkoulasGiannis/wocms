@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
    $count = $settings['count'] ?? 4;
    $columns = $settings['columns'] ?? 2;

    // Fetch latest blog posts
    $posts = \App\Models\ContentNode::where('template_id', function($query) {
            $query->select('id')
                  ->from('templates')
                  ->where('slug', 'blog-post')
                  ->limit(1);
        })
        ->where('is_published', true)
        ->orderBy('created_at', 'desc')
        ->limit($count)
        ->get();
@endphp

<section class="blog-posts-list py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        @if(!empty($content['heading']))
            <h2 class="text-4xl font-bold text-center mb-4">{{ $content['heading'] }}</h2>
        @endif

        @if(!empty($content['subheading']))
            <p class="text-xl text-gray-600 text-center mb-12">{{ $content['subheading'] }}</p>
        @endif

        <div class="grid md:grid-cols-{{ $columns }} gap-8">
            @foreach($posts as $post)
                @php
                    $blogPost = $post->content;
                @endphp

                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                    @if($blogPost->featured_image)
                        <img src="{{ $blogPost->getFeaturedImageUrl() }}"
                             alt="{{ $post->title }}"
                             class="w-full h-48 object-cover">
                    @endif

                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-3">
                            <a href="{{ $post->url_path }}" class="hover:text-blue-600 transition">
                                {{ $post->title }}
                            </a>
                        </h3>

                        @if(($settings['show_excerpt'] ?? true) && $blogPost->excerpt)
                            <p class="text-gray-600 mb-4">{{ $blogPost->excerpt }}</p>
                        @endif

                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            @if(($settings['show_date'] ?? true) && $blogPost->published_at)
                                <span>{{ $blogPost->published_at->format('d/m/Y') }}</span>
                            @endif

                            @if(($settings['show_author'] ?? false) && $blogPost->author)
                                <span>• {{ $blogPost->author }}</span>
                            @endif
                        </div>

                        <a href="{{ $post->url_path }}"
                           class="inline-block mt-4 text-blue-600 hover:text-blue-800 font-semibold">
                            Read More →
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
