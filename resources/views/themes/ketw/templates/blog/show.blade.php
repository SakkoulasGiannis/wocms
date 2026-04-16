@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $post->title)

@section('content')
    <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">

        {{-- Draft banner --}}
        @auth
            @if(auth()->user()->canViewDrafts() && $post->status !== 'active')
                <div class="mb-6 flex items-start gap-3 rounded-lg border-l-4 border-amber-400 bg-amber-50 p-4">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    <p class="text-sm text-amber-800">
                        <strong>Preview Mode:</strong> This post is <strong class="uppercase">{{ $post->status }}</strong> and not visible to the public.
                    </p>
                </div>
            @endif
        @endauth

        {{-- Back link --}}
        <a href="{{ route('blog.index') }}" class="mb-6 inline-flex items-center gap-1.5 text-sm text-brand hover:text-brand-dark">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Back to Blog
        </a>

        {{-- Featured image --}}
        @if($post->getFirstMediaUrl('featured_image'))
            <div class="mb-8 overflow-hidden rounded-2xl bg-slate-100">
                <img src="{{ $post->getFirstMediaUrl('featured_image') }}" alt="{{ $post->title }}" class="max-h-[480px] w-full object-cover">
            </div>
        @endif

        {{-- Title + Meta --}}
        <header class="mb-8">
            <h1 class="text-3xl font-bold leading-tight text-slate-900 md:text-5xl">{{ $post->title }}</h1>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-slate-600">
                @if($post->author)
                    <div class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                        {{ $post->author }}
                    </div>
                @endif
                @if($post->published_at)
                    <div class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>
                        {{ \Carbon\Carbon::parse($post->published_at)->format('F j, Y') }}
                    </div>
                @endif
            </div>

            @if($post->tags)
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach(array_map('trim', explode(',', $post->tags)) as $tag)
                        <a
                            href="{{ route('blog.index', ['tag' => $tag]) }}"
                            class="rounded-full bg-brand/10 px-3 py-1 text-xs font-medium text-brand hover:bg-brand/20"
                        >
                            {{ $tag }}
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        {{-- Excerpt --}}
        @if($post->excerpt)
            <div class="mb-8 rounded-r-lg border-l-4 border-brand bg-slate-50 p-6">
                <p class="text-lg leading-relaxed text-slate-700">{{ $post->excerpt }}</p>
            </div>
        @endif

        {{-- Body --}}
        <div class="prose prose-lg prose-slate mb-12 max-w-none prose-headings:text-slate-900 prose-a:text-brand hover:prose-a:text-brand-dark">
            {!! $post->body !!}
        </div>

        @if($post->body_css)
            <style>{!! $post->body_css !!}</style>
        @endif

        <hr class="my-12 border-slate-200">

        {{-- Related Posts --}}
        @if($relatedPosts->isNotEmpty())
            <section>
                <h2 class="text-2xl font-bold text-slate-900 md:text-3xl">Related Posts</h2>
                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach($relatedPosts as $relatedPost)
                        <article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-xl">
                            <a href="{{ route('blog.show', $relatedPost->slug) }}" class="block aspect-16/9 overflow-hidden bg-slate-100">
                                @if($relatedPost->getFirstMediaUrl('featured_image'))
                                    <img src="{{ $relatedPost->getFirstMediaUrl('featured_image') }}" alt="{{ $relatedPost->title }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-brand to-brand-dark"></div>
                                @endif
                            </a>
                            <div class="p-4">
                                <h3 class="font-semibold text-slate-900 group-hover:text-brand">
                                    <a href="{{ route('blog.show', $relatedPost->slug) }}">{{ $relatedPost->title }}</a>
                                </h3>
                                @if($relatedPost->excerpt)
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ Str::limit($relatedPost->excerpt, 100) }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Back link (bottom) --}}
        <div class="mt-12 text-center">
            <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-dark">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Back to All Posts
            </a>
        </div>
    </article>
@endsection
