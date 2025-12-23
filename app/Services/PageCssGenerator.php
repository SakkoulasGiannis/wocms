<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PageCssGenerator
{
    /**
     * Generate CSS file for a page/entry
     *
     * @param string $slug - The page slug (e.g., 'home', 'about-us')
     * @param string $css - The CSS content
     * @return string|null - Returns the CSS file URL or null on failure
     */
    public function generateCssFile(string $slug, string $css): ?string
    {
        if (empty($css)) {
            // If no CSS, delete the file if it exists
            $this->deleteCssFile($slug);
            return null;
        }

        // Create the CSS directory if it doesn't exist
        $cssDir = public_path('css/pages');
        if (!File::exists($cssDir)) {
            File::makeDirectory($cssDir, 0755, true);
        }

        // Generate filename based on slug
        $filename = Str::slug($slug) . '.css';
        $filePath = $cssDir . '/' . $filename;

        // Write CSS to file
        try {
            File::put($filePath, $css);

            // Return the public URL
            return asset('css/pages/' . $filename);
        } catch (\Exception $e) {
            \Log::error('Failed to generate CSS file: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete CSS file for a page/entry
     *
     * @param string $slug - The page slug
     * @return bool
     */
    public function deleteCssFile(string $slug): bool
    {
        $filename = Str::slug($slug) . '.css';
        $filePath = public_path('css/pages/' . $filename);

        if (File::exists($filePath)) {
            try {
                File::delete($filePath);
                return true;
            } catch (\Exception $e) {
                \Log::error('Failed to delete CSS file: ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Get CSS file URL for a page/entry
     *
     * @param string $slug - The page slug
     * @return string|null - Returns the CSS file URL or null if file doesn't exist
     */
    public function getCssFileUrl(string $slug): ?string
    {
        $filename = Str::slug($slug) . '.css';
        $filePath = public_path('css/pages/' . $filename);

        if (File::exists($filePath)) {
            return asset('css/pages/' . $filename);
        }

        return null;
    }

    /**
     * Check if CSS file exists for a page/entry
     *
     * @param string $slug - The page slug
     * @return bool
     */
    public function cssFileExists(string $slug): bool
    {
        $filename = Str::slug($slug) . '.css';
        $filePath = public_path('css/pages/' . $filename);

        return File::exists($filePath);
    }
}
