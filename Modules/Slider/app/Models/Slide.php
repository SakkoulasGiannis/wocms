<?php

namespace Modules\Slider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Slide extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'slider_id',
        'title',
        'description',
        'link',
        'button_text',
        'media_type',
        'video_url',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function slider(): BelongsTo
    {
        return $this->belongsTo(Slider::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('video')->singleFile();
    }

    /**
     * Extract YouTube video ID from URL
     */
    public function getYoutubeIdAttribute(): ?string
    {
        if ($this->media_type !== 'youtube' || ! $this->video_url) {
            return null;
        }

        preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $this->video_url, $matches);

        return $matches[1] ?? null;
    }

    public function registerMediaConversions(?BaseMedia $media = null): void
    {
        // Small thumb — used in the right-rail slider thumbnails (80x80 in UI, retina = ~160x160)
        $this->addMediaConversion('thumb')
            ->fit(\Spatie\Image\Enums\Fit::Crop, 300, 200)
            ->format('webp')
            ->quality(75)
            ->nonQueued();

        // Medium — used in card grids (e.g. listing pages, related sliders)
        $this->addMediaConversion('preview')
            ->fit(\Spatie\Image\Enums\Fit::Crop, 800, 450)
            ->format('webp')
            ->quality(78)
            ->nonQueued();

        // Hero — full-bleed slider on desktop (1920x1080 covers 4K with browser scaling).
        // WebP cuts ~40% off vs JPEG. quality 80 is visually transparent for photos.
        $this->addMediaConversion('hero')
            ->fit(\Spatie\Image\Enums\Fit::Crop, 1920, 1080)
            ->format('webp')
            ->quality(80)
            ->nonQueued();
    }
}
