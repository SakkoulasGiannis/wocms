<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Portfolio extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'portfolios';

    protected $fillable = [
        'title', 'slug', 'tagline', 'description', 'featured_image', 'gallery', 'technologies', 'features', 'client', 'website_url', 'github_url', 'year', 'status', 'featured'
    ];

    protected $casts = [
        'gallery' => 'array',
        'technologies' => 'array',
        'features' => 'array',
        'year' => 'integer',
        'featured' => 'boolean',
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
     * Get URLs for Screenshots Gallery
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
     * Get first image from Screenshots Gallery
     */
    public function getFirstGalleryUrl(): ?string
    {
        $urls = $this->getGalleryUrls();
        return !empty($urls) ? $urls[0] : null;
    }

    /**
     * Check if Screenshots Gallery has images
     */
    public function hasGallery(): bool
    {
        return is_array($this->gallery) && count($this->gallery) > 0;
    }

    /**
     * Get count of images in Screenshots Gallery
     */
    public function getGalleryCount(): int
    {
        return is_array($this->gallery) ? count($this->gallery) : 0;
    }

    /**
     * Add item to Technologies
     */
    public function addTechnology($item): void
    {
        $items = $this->technologies ?? [];
        $items[] = $item;
        $this->technologies = $items;
        $this->save();
    }

    /**
     * Remove item from Technologies by index
     */
    public function removeTechnology($index): void
    {
        $items = $this->technologies ?? [];
        if (isset($items[$index])) {
            unset($items[$index]);
            $this->technologies = array_values($items);
            $this->save();
        }
    }

    /**
     * Get count of items in Technologies
     */
    public function getTechnologyCount(): int
    {
        return is_array($this->technologies) ? count($this->technologies) : 0;
    }

    /**
     * Add item to Key Features
     */
    public function addFeature($item): void
    {
        $items = $this->features ?? [];
        $items[] = $item;
        $this->features = $items;
        $this->save();
    }

    /**
     * Remove item from Key Features by index
     */
    public function removeFeature($index): void
    {
        $items = $this->features ?? [];
        if (isset($items[$index])) {
            unset($items[$index]);
            $this->features = array_values($items);
            $this->save();
        }
    }

    /**
     * Get count of items in Key Features
     */
    public function getFeatureCount(): int
    {
        return is_array($this->features) ? count($this->features) : 0;
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
        $this->featured = !$this->featured;
        $this->save();
        return $this->featured;
    }
}