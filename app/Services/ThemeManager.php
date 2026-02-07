<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Models\Setting;

class ThemeManager
{
    /**
     * Cache TTL for theme data (24 hours)
     */
    const CACHE_TTL = 86400;

    /**
     * Base path for themes
     */
    protected string $themesPath;

    /**
     * Active theme slug
     */
    protected ?string $activeTheme = null;

    /**
     * Theme metadata cache
     */
    protected ?array $metadata = null;

    public function __construct()
    {
        $this->themesPath = resource_path('views/themes');
    }

    /**
     * Get the active theme slug
     */
    public function getActiveTheme(): string
    {
        if ($this->activeTheme !== null) {
            return $this->activeTheme;
        }

        $this->activeTheme = Cache::remember('active_theme', self::CACHE_TTL, function () {
            return Setting::get('active_theme', 'tailwind');
        });

        return $this->activeTheme;
    }

    /**
     * Set the active theme
     */
    public function setActiveTheme(string $slug): void
    {
        if (!$this->themeExists($slug)) {
            throw new \Exception("Theme '{$slug}' does not exist.");
        }

        Setting::set('active_theme', $slug);
        Cache::forget('active_theme');
        Cache::forget("theme_metadata.{$slug}");
        $this->activeTheme = $slug;
    }

    /**
     * Check if theme exists
     */
    public function themeExists(string $slug): bool
    {
        return File::isDirectory($this->themesPath . '/' . $slug);
    }

    /**
     * Get theme metadata
     */
    public function getMetadata(string $slug = null): ?array
    {
        $slug = $slug ?? $this->getActiveTheme();

        if ($this->metadata !== null && isset($this->metadata[$slug])) {
            return $this->metadata[$slug];
        }

        return Cache::remember("theme_metadata.{$slug}", self::CACHE_TTL, function () use ($slug) {
            $metadataPath = $this->themesPath . '/' . $slug . '/theme.json';

            if (!File::exists($metadataPath)) {
                return null;
            }

            $json = File::get($metadataPath);
            return json_decode($json, true);
        });
    }

    /**
     * Get all available themes
     */
    public function getAvailableThemes(): array
    {
        $themes = [];
        $directories = File::directories($this->themesPath);

        foreach ($directories as $dir) {
            $slug = basename($dir);
            $metadata = $this->getMetadata($slug);

            if ($metadata) {
                $themes[$slug] = $metadata;
            }
        }

        return $themes;
    }

    /**
     * Get layout view path
     */
    public function getLayout(string $layout = 'layout'): string
    {
        $theme = $this->getActiveTheme();

        // Check if theme has this layout
        $themeLayout = "themes.{$theme}.{$layout}";
        if (view()->exists($themeLayout)) {
            return $themeLayout;
        }

        // Fallback to default theme
        if ($theme !== 'tailwind') {
            $defaultLayout = "themes.tailwind.{$layout}";
            if (view()->exists($defaultLayout)) {
                return $defaultLayout;
            }
        }

        // Final fallback to old structure
        return "frontend.{$layout}";
    }

    /**
     * Get partial view path
     */
    public function getPartial(string $partial): string
    {
        $theme = $this->getActiveTheme();

        // Check theme partials
        $themePartial = "themes.{$theme}.partials.{$partial}";
        if (view()->exists($themePartial)) {
            return $themePartial;
        }

        // Fallback to default theme
        if ($theme !== 'tailwind') {
            $defaultPartial = "themes.tailwind.partials.{$partial}";
            if (view()->exists($defaultPartial)) {
                return $defaultPartial;
            }
        }

        // Final fallback
        return "frontend.partials.{$partial}";
    }

    /**
     * Get template view path with theme override support
     */
    public function getTemplateView(string $template): ?string
    {
        $theme = $this->getActiveTheme();

        // Priority 1: Theme template override
        $themeTemplate = "themes.{$theme}.templates.{$template}";
        if (view()->exists($themeTemplate)) {
            return $themeTemplate;
        }

        // Priority 2: Default theme template
        if ($theme !== 'tailwind') {
            $defaultTemplate = "themes.tailwind.templates.{$template}";
            if (view()->exists($defaultTemplate)) {
                return $defaultTemplate;
            }
        }

        // Priority 3: Original frontend views
        $frontendTemplate = "frontend.{$template}";
        if (view()->exists($frontendTemplate)) {
            return $frontendTemplate;
        }

        return null;
    }

    /**
     * Render theme CSS assets
     */
    public function renderCssAssets(): string
    {
        $metadata = $this->getMetadata();

        if (!$metadata || empty($metadata['assets']['css'])) {
            return '';
        }

        $html = '';
        foreach ($metadata['assets']['css'] as $css) {
            $path = "/themes/{$this->getActiveTheme()}/{$css}";
            $html .= "<link rel=\"stylesheet\" href=\"{$path}\">\n";
        }

        return $html;
    }

    /**
     * Render theme JS assets
     */
    public function renderJsAssets(): string
    {
        $metadata = $this->getMetadata();

        if (!$metadata || empty($metadata['assets']['js'])) {
            return '';
        }

        $html = '';
        foreach ($metadata['assets']['js'] as $js) {
            $path = "/themes/{$this->getActiveTheme()}/{$js}";
            $html .= "<script src=\"{$path}\"></script>\n";
        }

        return $html;
    }

    /**
     * Check if theme uses Vite
     */
    public function usesVite(): bool
    {
        $metadata = $this->getMetadata();
        return $metadata['vite'] ?? false;
    }

    /**
     * Get theme asset path
     */
    public function asset(string $path): string
    {
        return "/themes/{$this->getActiveTheme()}/{$path}";
    }

    /**
     * Clear all theme caches
     */
    public function clearCache(): void
    {
        Cache::forget('active_theme');

        $themes = $this->getAvailableThemes();
        foreach (array_keys($themes) as $slug) {
            Cache::forget("theme_metadata.{$slug}");
        }
    }
}
