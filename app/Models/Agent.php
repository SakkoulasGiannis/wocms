<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Agent extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $table = 'agents';

    protected $fillable = [
        'name', 'slug', 'role', 'photo', 'email', 'phone', 'bio', 'facebook', 'instagram', 'linkedin', 'twitter', 'active', 'order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Get the URL for Photo (preferring Spatie Media Library).
     */
    public function getPhotoUrl(string $conversion = ''): ?string
    {
        $mediaUrl = $this->getFirstMediaUrl('photo', $conversion);
        if ($mediaUrl) {
            return $mediaUrl;
        }

        if (! $this->photo) {
            return null;
        }

        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        return asset('storage/'.$this->photo);
    }

    /**
     * Get thumb URL (fallback to full photo if no conversion).
     */
    public function getThumbUrl(): ?string
    {
        return $this->getPhotoUrl('thumb') ?: $this->getPhotoUrl();
    }

    /**
     * Get medium URL (fallback to full photo if no conversion).
     */
    public function getMediumUrl(): ?string
    {
        return $this->getPhotoUrl('medium') ?: $this->getPhotoUrl();
    }

    /**
     * Check if Photo exists.
     */
    public function hasPhoto(): bool
    {
        return $this->getFirstMediaUrl('photo') !== '' || ! empty($this->photo);
    }

    /**
     * Check if Active is true.
     */
    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /**
     * Toggle Active.
     */
    public function toggleActive(): bool
    {
        $this->active = ! $this->active;
        $this->save();

        return $this->active;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?BaseMedia $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(250)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(750)
            ->nonQueued();
    }

    /**
     * Scope a query to only include active entries.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query ordered by order ASC then name ASC.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }
}
