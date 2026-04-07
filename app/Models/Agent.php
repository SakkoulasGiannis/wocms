<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Agent extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $table = 'agents';

    protected $fillable = [
        'name', 'slug', 'role', 'photo', 'email', 'phone', 'bio', 'facebook', 'instagram', 'linkedin', 'twitter', 'active', 'order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the URL for Photo
     */
    public function getPhotoUrl(): ?string
    {
        // Try Spatie Media Library first
        $mediaUrl = $this->getFirstMediaUrl('photo');
        if ($mediaUrl) {
            return $mediaUrl;
        }

        if (! $this->photo) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        // Otherwise, assume it's in storage
        return asset('storage/'.$this->photo);
    }

    /**
     * Check if Photo exists
     */
    public function hasPhoto(): bool
    {
        return $this->getFirstMediaUrl('photo') !== '' || ! empty($this->photo);
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
     * Scope a query to only include active (published) entries
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
