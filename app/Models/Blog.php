<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Blog extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'blogs';

    protected $fillable = [
        'title', 'slug', 'excerpt', 'featured_image', 'body', 'author', 'tags_legacy', 'published_at', 'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical_url', 'seo_focus_keyword', 'seo_robots_index', 'seo_robots_follow', 'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_type', 'seo_og_url', 'seo_twitter_card', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_image', 'seo_twitter_site', 'seo_twitter_creator', 'seo_schema_type', 'seo_schema_custom', 'seo_redirect_url', 'seo_redirect_type', 'seo_sitemap_include', 'seo_sitemap_priority', 'seo_sitemap_changefreq', 'render_mode', 'status', 'created_at'
    ];

    /**
     * Categories (hierarchical taxonomy).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<BlogCategory>
     */
    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_blog_category');
    }

    /**
     * Tags (flat taxonomy). Auto-created from the blog edit form.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<BlogTag>
     */
    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_blog_tag');
    }
    /**
     * Get the URL for Featured Image
     */
    public function getFeaturedImageUrl(): ?string
    {
        if (!$this->featured_image) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->featured_image, FILTER_VALIDATE_URL)) {
            return $this->featured_image;
        }

        // Otherwise, assume it's in storage
        return asset('storage/' . $this->featured_image);
    }

    /**
     * Check if Featured Image exists
     */
    public function hasFeaturedImage(): bool
    {
        return !empty($this->featured_image);
    }
    /**
     * Scope a query to only include active (published) entries
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now())
                     ->where(function ($q) {
                         $q->whereIn('status', ['published', 'active'])
                           ->orWhereNull('status');
                     });
    }
}