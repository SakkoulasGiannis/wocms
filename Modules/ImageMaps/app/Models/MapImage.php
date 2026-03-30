<?php

namespace Modules\ImageMaps\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MapImage extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'image_map_images';

    protected $fillable = ['title', 'description'];

    public function imageMaps(): HasMany
    {
        return $this->hasMany(ImageMap::class, 'image_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(320)->nonQueued();
    }
}
