<?php

namespace Modules\RentalProperties\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RentalProperty extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'rental_properties';

    protected $fillable = [
        'external_id',
        'title', 'slug', 'description', 'property_type', 'status', 'price', 'currency',
        'area', 'land_size', 'bedrooms', 'bathrooms', 'rooms', 'garages', 'floor', 'year_built',
        'address', 'city', 'state', 'country', 'postal_code', 'latitude', 'longitude',
        'featured_image', 'gallery', 'video_url', 'virtual_tour_url',
        'features', 'nearby_amenities', 'floor_plans', 'attachments',
        'meta_title', 'meta_description', 'meta_keywords',
        'active', 'featured', 'views', 'order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean', 'featured' => 'boolean',
            'price' => 'decimal:2', 'area' => 'decimal:2', 'land_size' => 'decimal:2',
            'latitude' => 'decimal:8', 'longitude' => 'decimal:8',
            'gallery' => 'array', 'features' => 'array', 'nearby_amenities' => 'array',
            'floor_plans' => 'array', 'attachments' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($p) {
            if (empty($p->slug)) {
                $p->slug = Str::slug($p->title);
            }
        });
    }

    public static function getPropertyTypes(): array
    {
        return ['apartment' => 'Apartment', 'house' => 'House', 'villa' => 'Villa', 'studio' => 'Studio', 'office' => 'Office', 'commercial' => 'Commercial', 'land' => 'Land', 'other' => 'Other'];
    }

    public static function getStatuses(): array
    {
        return ['for_rent' => 'For Rent', 'rented' => 'Rented', 'available' => 'Available', 'off_market' => 'Off Market'];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->currency.' '.number_format($this->price, 2).'/mo';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(300)->height(200)->nonQueued();
        $this->addMediaConversion('medium')->width(600)->height(400)->nonQueued();
        $this->addMediaConversion('large')->width(1200)->height(800)->nonQueued();
    }
}
