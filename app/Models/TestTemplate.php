<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TestTemplate extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'test_templates';

    protected $fillable = [
        'title', 'slug', 'subtitle', 'email', 'website', 'excerpt', 'description', 'content', 'content_css', 'featured_image', 'price', 'rating', 'is_featured', 'published_date', 'event_datetime', 'blogs', 'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical_url', 'seo_focus_keyword', 'seo_robots_index', 'seo_robots_follow', 'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_type', 'seo_og_url', 'seo_twitter_card', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_image', 'seo_twitter_site', 'seo_twitter_creator', 'seo_schema_type', 'seo_schema_custom', 'seo_redirect_url', 'seo_redirect_type', 'seo_sitemap_include', 'seo_sitemap_priority', 'seo_sitemap_changefreq', 'render_mode', 'status', 'created_at'
    ];

    protected $casts = [
        'price' => 'integer',
        'rating' => 'float',
        'is_featured' => 'boolean',
        'published_date' => 'date',
        'event_datetime' => 'datetime',
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

    /**
     * Check if Featured is true
     */
    public function isIsFeatured(): bool
    {
        return (bool) $this->is_featured;
    }

    /**
     * Toggle Featured
     */
    public function toggleIsFeatured(): bool
    {
        $this->is_featured = !$this->is_featured;
        $this->save();
        return $this->is_featured;
    }

    /**
     * Get formatted Published Date
     */
    public function getPublishedDateFormatted($format = 'Y-m-d'): ?string
    {
        return $this->published_date ? $this->published_date->format($format) : null;
    }

    /**
     * Get Published Date for humans
     */
    public function getPublishedDateForHumans(): ?string
    {
        return $this->published_date ? $this->published_date->diffForHumans() : null;
    }

    /**
     * Get formatted Event Date & Time
     */
    public function getEventDatetimeFormatted($format = 'Y-m-d'): ?string
    {
        return $this->event_datetime ? $this->event_datetime->format($format) : null;
    }

    /**
     * Get Event Date & Time for humans
     */
    public function getEventDatetimeForHumans(): ?string
    {
        return $this->event_datetime ? $this->event_datetime->diffForHumans() : null;
    }

    /**
     * Get the 1 relation
     */
    public function blog()
    {
        return $this->belongsTo(\App\Models\Blog::class, 'blogs');
    }
}