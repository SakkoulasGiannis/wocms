<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageSize extends Model
{
    protected $fillable = [
        'name',
        'label',
        'width',
        'height',
        'mode',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'order' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        // Auto-generate name from label if not provided
        static::creating(function ($imageSize) {
            if (empty($imageSize->name) && !empty($imageSize->label)) {
                $imageSize->name = strtolower(str_replace(' ', '_', $imageSize->label));
            }
        });
    }

    /**
     * Get all active image sizes ordered
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get conversion configuration for Spatie Media Library
     */
    public function getConversionConfig(): array
    {
        return [
            'name' => $this->name,
            'width' => $this->width,
            'height' => $this->height,
            'mode' => $this->mode,
        ];
    }
}
