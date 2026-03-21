<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Property extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'properties';

    protected $fillable = [
        'title', 'slug', 'body', 'body_css', 'image', 'code', 'render_mode', 'status', 'created_at'
    ];

    protected $casts = [
        'image' => 'array',
    ];
    /**
     * Get URLs for image
     */
    public function getImageUrls(): array
    {
        if (!is_array($this->image)) {
            return [];
        }

        return array_map(function ($image) {
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }
            return asset('storage/' . $image);
        }, $this->image);
    }

    /**
     * Get first image from image
     */
    public function getFirstImageUrl(): ?string
    {
        $urls = $this->getImageUrls();
        return !empty($urls) ? $urls[0] : null;
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