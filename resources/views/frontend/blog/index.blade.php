@extends('frontend.layout')

@section('title', 'Blog')

@section('content')
<div class="container mx-auto px-4 py-12">
    <!-- Header -->
    <div class="mb-12 text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Blog</h1>
        <p class="text-xl text-gray-600">Articles, tutorials, and insights</p>
    </div>

    <!-- Filters -->
    <div class="mb-8 flex flex-wrap gap-4 items-center justify-between">
        <!-- Search -->
        <form method="GET" action="{{ route('blog.index') }}" class="flex-1 max-w-md">
            <div class="relative">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search posts..."
                    class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </form>

        <!-- Filters -->
        <div class="flex gap-2">
            @if(request('search') || request('tag') || request('author'))
                <a href="{{ route('blog.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Clear Filters
                </a>
            @endif
        </div>
    </div>

    <!-- Tags Filter -->
    @if($allTags->isNotEmpty())
    <div class="mb-8">
        <p class="text-sm text-gray-600 mb-2">Filter by tag:</p>
        <div class="flex flex-wrap gap-2">
            @foreach($allTags->take(20) as $tag)
                <a
                    href="{{ route('blog.index', ['tag' => $tag] + request()->except('tag')) }}"
                    class="px-3 py-1 text-sm rounded-full {{ request('tag') === $tag ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}"
                >
                    {{ $tag }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Blog Posts Grid -->
    @if($posts->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            @foreach($posts as $post)
                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    @if($post->getFirstMediaUrl('featured_image'))
                        <img src="{{ $post->getFirstMediaUrl('featured_image') }}" alt="{{ $post->title }}" class="w-full h-48 object-cover">
                    @else
                        <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-purple-600"></div>
                    @endif

                    <div class="p-6">
                        <div class="flex items-center text-sm text-gray-600 mb-3">
                            @if($post->author)
                                <span class="mr-4">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $post->author }}
                                </span>
                            @endif
                            @if($post->published_at)
                                <span>
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ \Carbon\Carbon::parse($post->published_at)->format('M d, Y') }}
                                </span>
                            @endif
                        </div>

                        <h2 class="text-xl font-bold text-gray-900 mb-2 hover:text-blue-600">
                            <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                            @auth
                                @if(auth()->user()->canViewDrafts() && $post->status !== 'active')
                                    <span class="ml-2 inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $post->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($post->status) }}
                                    </span>
                                @endif
                            @endauth
                        </h2>

                        @if($post->excerpt)
                            <p class="text-gray-600 mb-4 line-clamp-3">{{ $post->excerpt }}</p>
                        @endif

                        @if($post->tags)
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach(array_slice(array_map('trim', explode(',', $post->tags)), 0, 3) as $tag)
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif

                        <a href="{{ route('blog.show', $post->slug) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                            Read more →
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $posts->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-gray-600 text-lg">No blog posts found.</p>
            @if(request('search') || request('tag') || request('author'))
                <a href="{{ route('blog.index') }}" class="text-blue-600 hover:text-blue-800 mt-4 inline-block">
                    Clear filters to see all posts
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
