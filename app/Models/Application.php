<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Application extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'applications';

    protected $fillable = [
        'title', 'slug', 'body', 'image'
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
}