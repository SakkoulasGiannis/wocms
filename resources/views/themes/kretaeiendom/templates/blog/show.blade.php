@extends(app(\App\Services\ThemeManager::class)->getLayout())

@section('title', $post->title)

@section('content')
<!-- Title Page -->
<section class="flat-title-page" style="background-image: url('{{ $post->getFirstMediaUrl('featured_image') ?: '/themes/kretaeiendom/images/banner/flat-blog.jpg' }}'); background-color: #f0f2f5;">
    <div class="container">
        <div class="breadcrumb-content">
            <div class="breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                <span>/</span>
                <a href="{{ route('blog.index') }}">Blog</a>
                <span>/</span>
                <span>{{ Str::limit($post->title, 40) }}</span>
            </div>
            <h2>{{ $post->title }}</h2>
        </div>
    </div>
</section>

<section class="pt-5 pb-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Status Banner for Admins/Editors -->
                @auth
                    @if(auth()->user()->canViewDrafts() && $post->status !== 'active')
                        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                            <i class="icon-warning me-2"></i>
                            <div>
                                <strong>Preview Mode:</strong> This post is <strong class="text-uppercase">{{ $post->status }}</strong> and not visible to the public.
                            </div>
                        </div>
                    @endif
                @endauth

                <!-- Featured Image -->
                @if($post->getFirstMediaUrl('featured_image'))
                    <div class="mb-4">
                        <img src="{{ $post->getFirstMediaUrl('featured_image') }}" alt="{{ $post->title }}" class="img-fluid" style="width:100%; max-height:500px; object-fit:cover; border-radius:16px;">
                    </div>
                @endif

                <!-- Post Meta -->
                <div class="post-author style-1 mb-3">
                    @if($post->author)
                        <span><i class="icon-profile"></i> {{ $post->author }}</span>
                    @endif
                    @if($post->published_at)
                        <span><i class="icon-calendar"></i> {{ \Carbon\Carbon::parse($post->published_at)->format('F j, Y') }}</span>
                    @endif
                </div>

                <!-- Tags -->
                @if($post->tags)
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach(array_map('trim', explode(',', $post->tags)) as $tag)
                            <a href="{{ route('blog.index', ['tag' => $tag]) }}" class="badge rounded-pill bg-primary text-decoration-none" style="font-size: 13px; padding: 6px 14px;">
                                {{ $tag }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- Excerpt -->
                @if($post->excerpt)
                    <div class="p-4 mb-4" style="background-color: #f8f9fa; border-left: 4px solid #1563df; border-radius: 0 8px 8px 0;">
                        <p class="mb-0" style="font-size: 18px; color: #5c6368; line-height: 1.7;">{{ $post->excerpt }}</p>
                    </div>
                @endif

                <!-- Body Content -->
                <div class="blog-content mb-5" style="font-size: 16px; line-height: 1.8; color: #333;">
                    {!! $post->body !!}
                </div>

                <!-- Custom CSS -->
                @if($post->body_css)
                    <style>{!! $post->body_css !!}</style>
                @endif

            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Back to Blog -->
                <div class="mb-4">
                    <a href="{{ route('blog.index') }}" class="btn btn-outline-primary w-100">
                        <i class="icon-arrow-left me-2"></i> Back to Blog
                    </a>
                </div>

                <!-- Related Posts -->
                @if($relatedPosts->isNotEmpty())
                    <div class="mb-4">
                        <h5 class="mb-3" style="color: #161e2d; font-weight: 700;">Related Posts</h5>
                        @foreach($relatedPosts as $relatedPost)
                            <div class="flat-blog-item mb-3">
                                <div class="img-style">
                                    <a href="{{ route('blog.show', $relatedPost->slug) }}">
                                        @if($relatedPost->getFirstMediaUrl('featured_image'))
                                            <img src="{{ $relatedPost->getFirstMediaUrl('featured_image') }}" alt="{{ $relatedPost->title }}" style="width:100%; height:160px; object-fit:cover; border-radius:16px;">
                                        @else
                                            <div style="width:100%; height:160px; background: linear-gradient(135deg, #1563df 0%, #0f507e 100%); border-radius:16px;"></div>
                                        @endif
                                    </a>
                                    @if($relatedPost->published_at)
                                        <span class="date-post">{{ \Carbon\Carbon::parse($relatedPost->published_at)->format('M d') }}</span>
                                    @endif
                                </div>
                                <div class="content-box">
                                    <h6 class="title">
                                        <a href="{{ route('blog.show', $relatedPost->slug) }}">{{ $relatedPost->title }}</a>
                                    </h6>
                                    @if($relatedPost->excerpt)
                                        <p class="description" style="font-size:14px;">{{ Str::limit($relatedPost->excerpt, 80) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
