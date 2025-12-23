<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'posts';

    protected $fillable = [
        'title', 'image', 'slug', 'content', 'excerpt', 'featured_image', 'published_at'
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
    /**
     * Get the URL for image
     */
    public function getImageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Otherwise, assume it's in storage
        return asset('storage/' . $this->image);
    }

    /**
     * Check if image exists
     */
    public function hasImage(): bool
    {
        return !empty($this->image);
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
     * Get formatted Published Date
     */
    public function getPublishedAtFormatted($format = 'Y-m-d'): ?string
    {
        return $this->published_at ? $this->published_at->format($format) : null;
    }

    /**
     * Get Published Date for humans
     */
    public function getPublishedAtForHumans(): ?string
    {
        return $this->published_at ? $this->published_at->diffForHumans() : null;
    }
}