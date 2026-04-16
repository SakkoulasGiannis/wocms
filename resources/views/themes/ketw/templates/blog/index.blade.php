@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', 'Blog')

@section('content')
    <section class="bg-slate-50 border-b border-slate-200">
        <div class="mx-auto max-w-8xl px-4 py-12 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold text-slate-900 md:text-5xl">Blog</h1>
            <p class="mt-3 text-lg text-slate-600">Articles, tutorials, and insights</p>
        </div>
    </section>

    <section class="py-12">
        <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">

            {{-- Filters Bar --}}
            <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
                <form method="GET" action="{{ route('blog.index') }}" class="w-full max-w-md">
                    <div class="relative">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search posts..."
                            class="w-full rounded-full border-slate-300 bg-white px-4 py-2.5 pl-11 text-sm shadow-sm focus:border-brand focus:ring-brand"
                        >
                        <svg class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </form>

                @if(request('search') || request('tag') || request('author'))
                    <a href="{{ route('blog.index') }}" class="text-sm text-slate-600 underline underline-offset-4 hover:text-brand">
                        Clear filters
                    </a>
                @endif
            </div>

            {{-- Tags --}}
            @if($allTags->isNotEmpty())
                <div class="mb-10">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Filter by tag</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($allTags->take(20) as $tag)
                            <a
                                href="{{ route('blog.index', ['tag' => $tag] + request()->except('tag', 'page')) }}"
                                class="rounded-full px-3 py-1 text-xs font-medium transition-colors {{ request('tag') === $tag ? 'bg-brand text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                            >
                                {{ $tag }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Posts --}}
            @if($posts->count() > 0)
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($posts as $post)
                        <article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-xl">
                            <a href="{{ route('blog.show', $post->slug) }}" class="block aspect-16/9 overflow-hidden bg-slate-100">
                                @if($post->getFirstMediaUrl('featured_image'))
                                    <img
                                        src="{{ $post->getFirstMediaUrl('featured_image') }}"
                                        alt="{{ $post->title }}"
                                        loading="lazy"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                    >
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-brand to-brand-dark"></div>
                                @endif
                            </a>

                            <div class="p-6">
                                <div class="flex items-center gap-4 text-xs text-slate-500">
                                    @if($post->author)
                                        <span class="flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                            {{ $post->author }}
                                        </span>
                                    @endif
                                    @if($post->published_at)
                                        <span class="flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                                            {{ \Carbon\Carbon::parse($post->published_at)->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>

                                <h2 class="mt-3 text-lg font-semibold text-slate-900 group-hover:text-brand">
                                    <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                                    @auth
                                        @if(auth()->user()->canViewDrafts() && $post->status !== 'active')
                                            <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $post->status === 'draft' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800' }}">
                                                {{ ucfirst($post->status) }}
                                            </span>
                                        @endif
                                    @endauth
                                </h2>

                                @if($post->excerpt)
                                    <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $post->excerpt }}</p>
                                @endif

                                @if($post->tags)
                                    <div class="mt-4 flex flex-wrap gap-1.5">
                                        @foreach(array_slice(array_map('trim', explode(',', $post->tags)), 0, 3) as $tag)
                                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <a href="{{ route('blog.show', $post->slug) }}" class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand hover:gap-2 hover:text-brand-dark transition-all">
                                    Read more
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($posts->hasPages())
                    <div class="mt-12">
                        {{ $posts->links() }}
                    </div>
                @endif
            @else
                <div class="rounded-2xl bg-slate-50 py-16 text-center">
                    <p class="text-slate-600">No blog posts found.</p>
                    @if(request('search') || request('tag') || request('author'))
                        <a href="{{ route('blog.index') }}" class="mt-4 inline-block text-sm text-brand hover:text-brand-dark">
                            Clear filters to see all posts
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
