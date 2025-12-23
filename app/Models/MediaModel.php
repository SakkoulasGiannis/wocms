<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Illuminate\Database\Eloquent\Model;

/**
 * Dummy model for Media Library uploads
 * Used when uploading files without attaching to a specific model
 */
class MediaModel extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'media_models';

    protected $fillable = [
        'name',
    ];

    public function registerMediaConversions(?BaseMedia $media = null): void
    {
        // Get all active image sizes
        $imageSizes = ImageSize::where('is_active', true)->get();

        foreach ($imageSizes as $size) {
            $conversion = $this->addMediaConversion($size->name);

            // Apply conversion mode
            switch ($size->mode) {
                case 'crop':
                    $conversion->crop('crop-center', $size->width, $size->height);
                    break;
                case 'fit':
                    $conversion->fit('contain', $size->width, $size->height);
                    break;
                case 'resize':
                    $conversion->width($size->width)->height($size->height);
                    break;
            }

            // Only apply to images
            $conversion->performOnCollections('default')
                      ->nonQueued(); // Generate immediately
        }
    }
}
