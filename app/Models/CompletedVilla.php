<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CompletedVilla extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'completed_villas';

    protected $fillable = [
        'name', 'slug', 'main_image', 'gallery', 'location', 'year_built', 'building_size', 'plot_size', 'pool_size', 'drawn_by', 'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical_url', 'seo_focus_keyword', 'seo_robots_index', 'seo_robots_follow', 'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_type', 'seo_og_url', 'seo_twitter_card', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_image', 'seo_twitter_site', 'seo_twitter_creator', 'seo_schema_type', 'seo_schema_custom', 'seo_redirect_url', 'seo_redirect_type', 'seo_sitemap_include', 'seo_sitemap_priority', 'seo_sitemap_changefreq', 'render_mode', 'status', 'created_at'
    ];

    protected $casts = [
        'gallery' => 'array',
        'year_built' => 'integer',
        'building_size' => 'integer',
        'plot_size' => 'integer',
        'pool_size' => 'integer',
    ];
    /**
     * Get the URL for Main Photo
     */
    public function getMainImageUrl(): ?string
    {
        if (!$this->main_image) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->main_image, FILTER_VALIDATE_URL)) {
            return $this->main_image;
        }

        // Otherwise, assume it's in storage
        return asset('storage/' . $this->main_image);
    }

    /**
     * Check if Main Photo exists
     */
    public function hasMainImage(): bool
    {
        return !empty($this->main_image);
    }

    /**
     * Get URLs for Image Gallery
     */
    public function getGalleryUrls(): array
    {
        if (!is_array($this->gallery)) {
            return [];
        }

        return array_map(function ($image) {
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }
            return asset('storage/' . $image);
        }, $this->gallery);
    }

    /**
     * Get first image from Image Gallery
     */
    public function getFirstGalleryUrl(): ?string
    {
        $urls = $this->getGalleryUrls();
        return !empty($urls) ? $urls[0] : null;
    }

    /**
     * Check if Image Gallery has images
     */
    public function hasGallery(): bool
    {
        return is_array($this->gallery) && count($this->gallery) > 0;
    }

    /**
     * Get count of images in Image Gallery
     */
    public function getGalleryCount(): int
    {
        return is_array($this->gallery) ? count($this->gallery) : 0;
    }
    /**
     * Scope a query to only include active (published) entries.
     * Project templates use a simple status column (no scheduled publishing yet).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'published')
              ->orWhere('status', 'active')
              ->orWhereNull('status');
        });
    }
}