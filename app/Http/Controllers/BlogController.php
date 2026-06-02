<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Template;
use App\Services\ThemeManager;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts
     */
    public function index(Request $request)
    {
        $template = Template::where('slug', 'blog')->firstOrFail();

        // Check if template is publicly accessible
        if (! $template->is_public) {
            abort(403, 'This content is not publicly accessible');
        }

        // Build query - admins and editors can see all posts
        $query = Blog::query();

        // Only filter by active status if user is not admin/editor
        if (! auth()->check() || ! auth()->user()->canViewDrafts()) {
            $query->active();
        }

        $posts = $query->with(['tags', 'categories'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhereHas('tags', fn ($t) => $t->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->tag, function ($q, $tag) {
                // Filter by tag slug (preferred) or name (legacy)
                $q->whereHas('tags', fn ($t) => $t->where('slug', $tag)->orWhere('name', $tag));
            })
            ->when($request->category, function ($q, $cat) {
                $q->whereHas('categories', fn ($c) => $c->where('slug', $cat)->orWhere('name', $cat));
            })
            ->when($request->author, function ($q, $author) {
                $q->where('author', $author);
            })
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        // Taxonomy lists for filter dropdowns
        $allTags = \App\Models\BlogTag::orderBy('name')->get();
        $allCategories = \App\Models\BlogCategory::where('is_active', true)
            ->orderBy('order')->orderBy('name')->get();

        $allAuthors = Blog::active()
            ->whereNotNull('author')
            ->distinct()
            ->pluck('author')
            ->sort()
            ->values();

        $themeManager = app(ThemeManager::class);
        $view = $themeManager->getTemplateView('blog.index') ?? 'frontend.blog.index';

        return view($view, compact('posts', 'template', 'allTags', 'allCategories', 'allAuthors'));
    }

    /**
     * Filter blog by category slug — /blog/category/{slug}.
     * Reuses the index() view by populating $request->category.
     */
    public function category(Request $request, string $slug)
    {
        $category = \App\Models\BlogCategory::where('slug', $slug)->firstOrFail();
        $request->merge(['category' => $category->slug]);
        $response = $this->index($request);
        // share the filter context with the view so it can render a heading
        if (method_exists($response, 'with')) {
            $response->with(['activeCategory' => $category]);
        }

        return $response;
    }

    /**
     * Filter blog by tag slug — /blog/tag/{slug}.
     */
    public function tag(Request $request, string $slug)
    {
        $tag = \App\Models\BlogTag::where('slug', $slug)->firstOrFail();
        $request->merge(['tag' => $tag->slug]);
        $response = $this->index($request);
        if (method_exists($response, 'with')) {
            $response->with(['activeTag' => $tag]);
        }

        return $response;
    }

    /**
     * Display the specified blog post
     */
    public function show($slug)
    {
        \Log::info("🔵 BlogController::show() called for slug: {$slug}");

        $template = Template::where('slug', 'blog')->firstOrFail();

        // Check if template is publicly accessible
        if (! $template->is_public) {
            abort(403, 'This content is not publicly accessible');
        }

        // Check if caching is enabled for this template
        if ($template->enable_full_page_cache) {
            $cacheKey = "page.blog.{$slug}";
            $cacheTtl = $template->cache_ttl ?? 3600;

            // Check if cache exists
            if (\Cache::has($cacheKey)) {
                \Log::info("✅ CACHE HIT: /blog/{$slug} (serving from cache)");

                return response(\Cache::get($cacheKey));
            }

            \Log::info("❌ CACHE MISS: /blog/{$slug} (generating and caching for {$cacheTtl}s)");
        }

        // Build query - admins and editors can see all posts
        $query = Blog::query();

        // Only filter by active status if user is not admin/editor
        if (! auth()->check() || ! auth()->user()->canViewDrafts()) {
            $query->active();
        }

        $post = $query->where('slug', $slug)
            ->whereNotNull('published_at')
            ->firstOrFail();

        // Get related posts — share at least one tag with the current post.
        $relatedPosts = collect();
        $tagIds = $post->tags->pluck('id')->all();
        if ($tagIds) {
            $relatedPosts = Blog::active()
                ->where('id', '!=', $post->id)
                ->whereNotNull('published_at')
                ->whereHas('tags', fn ($q) => $q->whereIn('blog_tags.id', $tagIds))
                ->with('tags', 'categories')
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get();
        }

        $themeManager = app(ThemeManager::class);
        $viewName = $themeManager->getTemplateView('blog.show') ?? 'frontend.blog.show';
        $view = view($viewName, compact('post', 'template', 'relatedPosts'));

        // Cache the rendered HTML if caching is enabled
        if ($template->enable_full_page_cache) {
            $html = $view->render();
            \Cache::put($cacheKey, $html, $cacheTtl);
        }

        return $view;
    }
}
