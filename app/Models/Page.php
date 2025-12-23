<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'pages';

    protected $fillable = [
        'title', 'slug', 'body', 'body_css', 'featured_image', 'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical_url', 'seo_focus_keyword', 'seo_robots_index', 'seo_robots_follow', 'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_type', 'seo_og_url', 'seo_twitter_card', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_image', 'seo_twitter_site', 'seo_twitter_creator', 'seo_schema_type', 'seo_schema_custom', 'seo_redirect_url', 'seo_redirect_type', 'seo_sitemap_include', 'seo_sitemap_priority', 'seo_sitemap_changefreq', 'render_mode', 'status', 'created_at'
    ];
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
}