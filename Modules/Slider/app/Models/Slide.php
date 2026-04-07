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
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(450)
            ->nonQueued();
    }
}
