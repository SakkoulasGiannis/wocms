<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Template;
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
        if (!$template->is_public) {
            abort(403, 'This content is not publicly accessible');
        }

        // Build query - admins and editors can see all posts
        $query = Blog::query();

        // Only filter by active status if user is not admin/editor
        if (!auth()->check() || !auth()->user()->canViewDrafts()) {
            $query->active();
        }

        $posts = $query->when($request->search, function($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('excerpt', 'like', "%{$search}%")
                      ->orWhere('tags', 'like', "%{$search}%");
            })
            ->when($request->tag, function($query, $tag) {
                $query->where('tags', 'like', "%{$tag}%");
            })
            ->when($request->author, function($query, $author) {
                $query->where('author', $author);
            })
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        // Get all unique tags and authors for filters (only from active posts)
        $allTags = Blog::active()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(fn($tags) => array_map('trim', explode(',', $tags)))
            ->unique()
            ->sort()
            ->values();

        $allAuthors = Blog::active()
            ->whereNotNull('author')
            ->distinct()
            ->pluck('author')
            ->sort()
            ->values();

        return view('frontend.blog.index', compact('posts', 'template', 'allTags', 'allAuthors'));
    }

    /**
     * Display the specified blog post
     */
    public function show($slug)
    {
        $template = Template::where('slug', 'blog')->firstOrFail();

        // Check if template is publicly accessible
        if (!$template->is_public) {
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
        if (!auth()->check() || !auth()->user()->canViewDrafts()) {
            $query->active();
        }

        $post = $query->where('slug', $slug)
            ->whereNotNull('published_at')
            ->firstOrFail();

        // Get related posts (same tags)
        $relatedPosts = collect();
        if ($post->tags) {
            $tags = array_map('trim', explode(',', $post->tags));
            $relatedPosts = Blog::active()
                ->where('id', '!=', $post->id)
                ->whereNotNull('published_at')
                ->where(function($query) use ($tags) {
                    foreach ($tags as $tag) {
                        $query->orWhere('tags', 'like', "%{$tag}%");
                    }
                })
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get();
        }

        $view = view('frontend.blog.show', compact('post', 'template', 'relatedPosts'));

        // Cache the rendered HTML if caching is enabled
        if ($template->enable_full_page_cache) {
            $html = $view->render();
            \Cache::put($cacheKey, $html, $cacheTtl);
        }

        return $view;
    }
}
