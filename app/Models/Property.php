<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Property extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $table = 'properties';

    protected $fillable = [
        'title', 'slug', 'body', 'body_css', 'description', 'image', 'property_type', 'code', 'status', 'price', 'city', 'bedrooms', 'bathrooms', 'area', 'active', 'featured', 'render_mode', 'status', 'created_at',
    ];

    protected $casts = [
        'image' => 'array',
        'price' => 'integer',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'area' => 'integer',
        'active' => 'boolean',
        'featured' => 'boolean',
    ];

    /**
     * Get URLs for image
     */
    public function getImageUrls(): array
    {
        if (! is_array($this->image)) {
            return [];
        }

        return array_map(function ($image) {
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }

            return asset('storage/'.$image);
        }, $this->image);
    }

    /**
     * Get first image from image
     */
    public function getFirstImageUrl(): ?string
    {
        $urls = $this->getImageUrls();

        return ! empty($urls) ? $urls[0] : null;
    }

    /**
     * Check if image has images
     */
    public function hasImage(): bool
    {
        return is_array($this->image) && count($this->image) > 0;
    }

    /**
     * Get count of images in image
     */
    public function getImageCount(): int
    {
        return is_array($this->image) ? count($this->image) : 0;
    }

    /**
     * Check if Active is true
     */
    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /**
     * Toggle Active
     */
    public function toggleActive(): bool
    {
        $this->active = ! $this->active;
        $this->save();

        return $this->active;
    }

    /**
     * Check if Featured is true
     */
    public function isFeatured(): bool
    {
        return (bool) $this->featured;
    }

    /**
     * Toggle Featured
     */
    public function toggleFeatured(): bool
    {
        $this->featured = ! $this->featured;
        $this->save();

        return $this->featured;
    }

    /**
     * Scope a query to only include active (published) entries
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->where('status', 'published')
                    ->orWhereNull('status');
            });
    }
}
