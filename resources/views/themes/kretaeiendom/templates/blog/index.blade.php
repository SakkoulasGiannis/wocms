@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', 'Blog')

@section('content')
<!-- Title Page -->
<section class="flat-title-page" style="background-image: url('/themes/kretaeiendom/images/banner/flat-blog.jpg'); background-color: #f0f2f5;">
    <div class="container">
        <div class="breadcrumb-content">
            <div class="breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <span>/</span>
                <span>Blog</span>
            </div>
            <h2>Blog</h2>
        </div>
    </div>
</section>

<!-- Blog Grid -->
<section class="pt-5 pb-5">
    <div class="container">

        <!-- Search & Filters -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" action="{{ route('blog.index') }}">
                    <div class="input-group">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search posts...">
                        <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="col-md-6 text-end">
                @if(request('search') || request('tag') || request('author'))
                    <a href="{{ route('blog.index') }}" class="btn btn-outline-secondary">Clear Filters</a>
                @endif
            </div>
        </div>

        <!-- Tags Filter -->
        @if($allTags->isNotEmpty())
        <div class="mb-4">
            <p class="text-muted mb-2">Filter by tag:</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach($allTags->take(20) as $tag)
                    <a href="{{ route('blog.index', ['tag' => $tag] + request()->except('tag')) }}"
                       class="badge rounded-pill {{ request('tag') === $tag ? 'bg-primary' : 'bg-light text-dark' }} text-decoration-none"
                       style="font-size: 13px; padding: 6px 14px;">
                        {{ $tag }}
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Posts Grid -->
        @if($posts->count() > 0)
            <div class="row">
                @foreach($posts as $post)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="flat-blog-item">
                            <div class="img-style">
                                <a href="{{ route('blog.show', $post->slug) }}">
                                    @if($post->getFirstMediaUrl('featured_image'))
                                        <img src="{{ $post->getFirstMediaUrl('featured_image') }}" alt="{{ $post->title }}" style="width:100%; height:220px; object-fit:cover; border-radius:16px;">
                                    @else
                                        <div style="width:100%; height:220px; background: linear-gradient(135deg, #1563df 0%, #0f507e 100%); border-radius:16px;"></div>
                                    @endif
                                </a>
                                @if($post->published_at)
                                    <span class="date-post">{{ \Carbon\Carbon::parse($post->published_at)->format('M d, Y') }}</span>
                                @endif
                            </div>
                            <div class="content-box">
                                <div class="post-author style-1">
                                    @if($post->author)
                                        <span><i class="icon-profile"></i> {{ $post->author }}</span>
                                    @endif
                                    @if($post->tags)
                                        @foreach(array_slice(array_map('trim', explode(',', $post->tags)), 0, 2) as $tag)
                                            <span>{{ $tag }}</span>
                                        @endforeach
                                    @endif
                                </div>
                                <h5 class="title">
                                    <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                                    @auth
                                        @if(auth()->user()->canViewDrafts() && $post->status !== 'active')
                                            <span class="badge bg-warning text-dark ms-1" style="font-size:11px;">{{ ucfirst($post->status) }}</span>
                                        @endif
                                    @endauth
                                </h5>
                                @if($post->excerpt)
                                    <p class="description">{{ Str::limit($post->excerpt, 120) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $posts->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <p class="text-muted fs-5">No blog posts found.</p>
                @if(request('search') || request('tag') || request('author'))
                    <a href="{{ route('blog.index') }}" class="btn btn-primary mt-3">Clear filters to see all posts</a>
                @endif
            </div>
        @endif

    </div>
</section>
@endsection
