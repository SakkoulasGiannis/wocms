<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Client extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'clients';

    protected $fillable = [
        'name', 'logo', 'website', 'description', 'order'
    ];

    protected $casts = [
        'order' => 'integer',
    ];
    /**
     * Get the URL for Logo
     */
    public function getLogoUrl(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        // Otherwise, assume it's in storage
        return asset('storage/' . $this->logo);
    }

    /**
     * Check if Logo exists
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo);
    }
}