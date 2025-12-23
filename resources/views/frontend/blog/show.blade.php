@extends('frontend.layout')

@section('title', $post->title)

@section('content')
<article class="container mx-auto px-4 py-12">
    <!-- Status Banner for Admins/Editors -->
    @auth
        @if(auth()->user()->canViewDrafts() && $post->status !== 'active')
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-yellow-700">
                        <strong>Preview Mode:</strong> This post is <strong class="uppercase">{{ $post->status }}</strong> and not visible to the public.
                    </p>
                </div>
            </div>
        @endif
    @endauth

    <!-- Back to Blog -->
    <div class="mb-6">
        <a href="{{ route('blog.index') }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Blog
        </a>
    </div>

    <!-- Featured Image -->
    @if($post->getFirstMediaUrl('featured_image'))
        <div class="mb-8 rounded-lg overflow-hidden">
            <img src="{{ $post->getFirstMediaUrl('featured_image') }}" alt="{{ $post->title }}" class="w-full max-h-96 object-cover">
        </div>
    @endif

    <!-- Title and Meta -->
    <header class="mb-8">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ $post->title }}</h1>

        <div class="flex flex-wrap items-center gap-4 text-gray-600">
            @if($post->author)
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $post->author }}</span>
                </div>
            @endif

            @if($post->published_at)
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ \Carbon\Carbon::parse($post->published_at)->format('F j, Y') }}</span>
                </div>
            @endif
        </div>

        <!-- Tags -->
        @if($post->tags)
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach(array_map('trim', explode(',', $post->tags)) as $tag)
                    <a
                        href="{{ route('blog.index', ['tag' => $tag]) }}"
                        class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200"
                    >
                        {{ $tag }}
                    </a>
                @endforeach
            </div>
        @endif
    </header>

    <!-- Excerpt -->
    @if($post->excerpt)
        <div class="mb-8 p-6 bg-gray-50 border-l-4 border-blue-500 rounded-r-lg">
            <p class="text-lg text-gray-700 leading-relaxed">{{ $post->excerpt }}</p>
        </div>
    @endif

    <!-- Body Content -->
    <div class="prose prose-lg max-w-none mb-12">
        {!! $post->body !!}
    </div>

    <!-- Custom CSS if exists -->
    @if($post->body_css)
        <style>
            {!! $post->body_css !!}
        </style>
    @endif

    <!-- Divider -->
    <hr class="my-12 border-gray-300">

    <!-- Related Posts -->
    @if($relatedPosts->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Related Posts</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($relatedPosts as $relatedPost)
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        @if($relatedPost->getFirstMediaUrl('featured_image'))
                            <img src="{{ $relatedPost->getFirstMediaUrl('featured_image') }}" alt="{{ $relatedPost->title }}" class="w-full h-40 object-cover">
                        @else
                            <div class="w-full h-40 bg-gradient-to-br from-blue-500 to-purple-600"></div>
                        @endif

                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 mb-2 hover:text-blue-600">
                                <a href="{{ route('blog.show', $relatedPost->slug) }}">{{ $relatedPost->title }}</a>
                            </h3>

                            @if($relatedPost->excerpt)
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ Str::limit($relatedPost->excerpt, 100) }}</p>
                            @endif

                            <a href="{{ route('blog.show', $relatedPost->slug) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                Read more →
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Back to Blog (bottom) -->
    <div class="mt-12 text-center">
        <a href="{{ route('blog.index') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            ← Back to All Posts
        </a>
    </div>
</article>
@endsection
