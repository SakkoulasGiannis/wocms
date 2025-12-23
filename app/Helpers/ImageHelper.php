<?php

namespace App\Helpers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImageHelper
{
    /**
     * Get image URL by size name using Spatie Media Library
     *
     * @param Media|int $media Media model or ID
     * @param string $sizeName Size name (e.g., 'thumbnail', 'small_thumbnail')
     * @param string|null $fallback Fallback URL if size doesn't exist
     * @return string
     */
    public static function getUrl($media, string $sizeName = 'original', ?string $fallback = null): string
    {
        if (is_int($media)) {
            $media = Media::find($media);
        }

        if (!$media) {
            return $fallback ?? '';
        }

        // If requesting original, return original URL
        if ($sizeName === 'original') {
            return $media->getUrl();
        }

        // Try to get conversion URL
        try {
            return $media->getUrl($sizeName);
        } catch (\Exception $e) {
            // Conversion doesn't exist, return original or fallback
            return $fallback ?? $media->getUrl();
        }
    }

    /**
     * Get responsive srcset attribute for an image
     *
     * @param Media|int $media
     * @param array $sizes Array of size names
     * @return string
     */
    public static function getSrcset($media, array $sizes = []): string
    {
        if (is_int($media)) {
            $media = Media::find($media);
        }

        if (!$media) {
            return '';
        }

        $srcset = [];
        $conversions = $media->generated_conversions ?? [];

        foreach ($sizes as $sizeName) {
            if (isset($conversions[$sizeName]) && $conversions[$sizeName] === true) {
                $url = self::getUrl($media, $sizeName);

                // Get size width from ImageSize model
                $imageSize = \App\Models\ImageSize::where('name', $sizeName)->first();
                if ($imageSize) {
                    $srcset[] = $url . ' ' . $imageSize->width . 'w';
                }
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Get picture element HTML with multiple sources
     *
     * @param Media|int $media
     * @param array $config ['thumbnail' => '(max-width: 600px)', 'medium' => '(max-width: 1200px)']
     * @param string $alt
     * @param string $class
     * @return string
     */
    public static function getPicture($media, array $config = [], string $alt = '', string $class = ''): string
    {
        if (is_int($media)) {
            $media = Media::find($media);
        }

        if (!$media) {
            return '';
        }

        $html = '<picture>';

        foreach ($config as $sizeName => $mediaQuery) {
            $url = self::getUrl($media, $sizeName);
            if ($url) {
                $html .= sprintf('<source media="%s" srcset="%s">', $mediaQuery, $url);
            }
        }

        // Fallback img tag
        $originalUrl = self::getUrl($media, 'original');
        $html .= sprintf('<img src="%s" alt="%s" class="%s" loading="lazy">', $originalUrl, $alt, $class);
        $html .= '</picture>';

        return $html;
    }
}
