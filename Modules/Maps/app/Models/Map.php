<?php

namespace Modules\Maps\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Map extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description',
        'default_lat', 'default_lng', 'default_zoom', 'min_zoom', 'max_zoom',
        'markers', 'areas',
        'show_controls', 'show_search', 'show_legend', 'legend_html',
        'meta_title', 'meta_description',
        'active', 'featured', 'views',
    ];

    protected function casts(): array
    {
        return [
            'default_lat' => 'decimal:8',
            'default_lng' => 'decimal:8',
            'markers' => 'array',
            'areas' => 'array',
            'show_controls' => 'boolean',
            'show_search' => 'boolean',
            'show_legend' => 'boolean',
            'active' => 'boolean',
            'featured' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($map) {
            if (empty($map->slug)) {
                $map->slug = Str::slug($map->title);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getMarkersCountAttribute(): int
    {
        return count($this->markers ?? []);
    }

    public function getAreasCountAttribute(): int
    {
        return count($this->areas ?? []);
    }
}
