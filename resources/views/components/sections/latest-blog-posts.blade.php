@props(['content' => [], 'settings' => []])

@php
    $subtitle = $content['subtitle'] ?? 'Latest New';
    $title = $content['title'] ?? 'The Most Recent Estate';
    $carouselSubtitle = $content['carousel_subtitle'] ?? 'Latest New';
    $carouselTitle = $content['carousel_title'] ?? 'From Our Blog';
    $limit = $content['limit'] ?? 8;

    // Try to load real blog posts from the database
    $blogPosts = collect();
    try {
        $blogPosts = \App\Models\Blog::active()->latest('published_at')->limit($limit)->get();
    } catch (\Exception $e) {
        // Table not available
    }

    // Fall back to static defaults if no real posts
    if ($blogPosts->isEmpty()) {
        $blogPosts = collect($content['posts'] ?? [
            [
                'title' => 'Building gains into housing stocks...',
                'date' => 'January 28, 2024',
                'author' => 'Jerome Bell',
                'category' => 'Furniture',
                'image' => '/themes/kretaeiendom/images/blog/blog-20.jpg',
                'link' => '#',
                'description' => 'The average contract interest rate for 30-year fixed-rate mortgages with conforming loan balances...',
            ],
            [
                'title' => '92% of millennial home buyers say inflation...',
                'date' => 'January 28, 2024',
                'author' => 'Jerome Bell',
                'category' => 'Furniture',
                'image' => '/themes/kretaeiendom/images/blog/blog-21.jpg',
                'link' => '#',
                'description' => 'The average contract interest rate for 30-year fixed-rate mortgages with conforming loan balances...',
            ],
            [
                'title' => 'Building gains into housing stocks and how...',
                'date' => 'January 28, 2024',
                'author' => 'Jerome Bell',
                'category' => 'Furniture',
                'image' => '/themes/kretaeiendom/images/blog/blog-22.jpg',
                'link' => '#',
                'description' => 'The average contract interest rate for 30-year fixed-rate mortgages with conforming loan balances...',
            ],
            [
                'title' => 'We are hiring moderately, says Compass CEO...',
                'date' => 'January 28, 2024',
                'author' => 'Jerome Bell',
                'category' => 'Furniture',
                'image' => '/themes/kretaeiendom/images/blog/blog-23.jpg',
                'link' => '#',
                'description' => 'The average contract interest rate for 30-year fixed-rate mortgages with conforming loan balances...',
            ],
            [
                'title' => 'Building gains into housing stocks and how to trade the sector',
                'date' => 'January 28, 2024',
                'author' => 'Jerome Bell',
                'category' => 'Furniture',
                'image' => '/themes/kretaeiendom/images/blog/blog-17.jpg',
                'link' => '#',
                'description' => 'The average contract interest rate for 30-year fixed-rate mortgages with conforming loan balances...',
            ],
            [
                'title' => 'Building gains into housing stocks and how to trade the sector',
                'date' => 'January 28, 2024',
                'author' => 'Jerome Bell',
                'category' => 'Furniture',
                'image' => '/themes/kretaeiendom/images/blog/blog-18.jpg',
                'link' => '#',
                'description' => 'The average contract interest rate for 30-year fixed-rate mortgages with conforming loan balances...',
            ],
            [
                'title' => 'Building gains into housing stocks and how to trade the sector',
                'date' => 'January 28, 2024',
                'author' => 'Jerome Bell',
                'category' => 'Furniture',
                'image' => '/themes/kretaeiendom/images/blog/blog-19.jpg',
                'link' => '#',
                'description' => 'The average contract interest rate for 30-year fixed-rate mortgages with conforming loan balances...',
            ],
        ]);
        $isStatic = true;
    }

    $isEloquent = $blogPosts->isNotEmpty() && isset($blogPosts->first()->id) && !isset($isStatic);
    $gridPosts = $blogPosts->take(4);
    $carouselPosts = $blogPosts->count() > 4 ? $blogPosts->slice(4) : $blogPosts->take(3);
@endphp

<!-- Latest new -->
<section class="flat-section pt-0">
    <div class="container">
        <div class="box-title text-center wow fadeInUp">
            <div class="text-subtitle text-primary">{{ $subtitle }}</div>
            <h3 class="title mt-4">{{ $title }}</h3>
        </div>
        <div class="tf-grid-layout xl-col-4 sm-col-2 wow fadeInUp" data-wow-delay=".2s">
            @foreach($gridPosts as $post)
                @php
                    $postTitle = $isEloquent ? $post->title : ($post['title'] ?? '');
                    $postDate = $isEloquent ? ($post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('F d, Y') : $post->created_at->format('F d, Y')) : ($post['date'] ?? '');
                    $postAuthor = $isEloquent ? ($post->author ?? 'Admin') : ($post['author'] ?? 'Admin');
                    $postCategory = $isEloquent ? 'Blog' : ($post['category'] ?? 'Blog');
                    $postImage = $isEloquent ? ($post->getFeaturedImageUrl() ?? '/themes/kretaeiendom/images/blog/blog-20.jpg') : ($post['image'] ?? '/themes/kretaeiendom/images/blog/blog-20.jpg');
                    $postLink = $isEloquent ? url('/blog/' . $post->slug) : ($post['link'] ?? '#');
                @endphp
                <a href="{{ $postLink }}" class="flat-blog-item hover-img style-1">
                    <div class="img-style">
                        <img class="lazyload" data-src="{{ $postImage }}" src="{{ $postImage }}" alt="img-blog">
                    </div>
                    <span class="date-post">{{ $postDate }}</span>
                    <div class="content-box">
                        <h6 class="title">{{ $postTitle }}</h6>
                        <div class="post-author">
                            <span class="fw-6">{{ $postAuthor }}</span>
                            <span>{{ $postCategory }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
<!-- /Latest new -->

<!-- Latest New Carousel -->
<section class="flat-section bg-primary-new">
    <div class="container">
        <div class="box-title text-center wow fadeInUp">
            <div class="text-subtitle text-primary">{{ $carouselSubtitle }}</div>
            <h3 class="title mt-4">{{ $carouselTitle }}</h3>
        </div>
        <div dir="ltr" class="swiper tf-sw-latest" data-preview="3" data-tablet="2" data-mobile-sm="2" data-mobile="1" data-space-lg="30" data-space-md="15" data-space="15">
            <div class="swiper-wrapper wow fadeInUp" data-wow-delay=".2s">
                @foreach($carouselPosts as $post)
                    @php
                        $postTitle = $isEloquent ? $post->title : ($post['title'] ?? '');
                        $postDate = $isEloquent ? ($post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('F d, Y') : $post->created_at->format('F d, Y')) : ($post['date'] ?? '');
                        $postAuthor = $isEloquent ? ($post->author ?? 'Admin') : ($post['author'] ?? 'Jerome Bell');
                        $postCategory = $isEloquent ? 'Blog' : ($post['category'] ?? 'Furniture');
                        $postImage = $isEloquent ? ($post->getFeaturedImageUrl() ?? '/themes/kretaeiendom/images/blog/blog-17.jpg') : ($post['image'] ?? '/themes/kretaeiendom/images/blog/blog-17.jpg');
                        $postLink = $isEloquent ? route('blog.show', $post->slug) : ($post['link'] ?? '#');
                        $postDescription = $isEloquent ? \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?? $post->body ?? ''), 120) : ($post['description'] ?? '');
                    @endphp
                    <div class="swiper-slide">
                        <a href="{{ $postLink }}" class="flat-blog-item hover-img">
                            <div class="img-style">
                                <img class="lazyload" data-src="{{ $postImage }}" src="{{ $postImage }}" alt="img-blog">
                                <span class="date-post">{{ $postDate }}</span>
                            </div>
                            <div class="content-box">
                                <div class="post-author">
                                    <span class="fw-6">{{ $postAuthor }}</span>
                                    <span>{{ $postCategory }}</span>
                                </div>
                                <h5 class="title link">{{ $postTitle }}</h5>
                                <p class="description">{{ $postDescription }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
            <div class="sw-pagination sw-pagination-latest text-center"></div>
        </div>
    </div>
</section>
