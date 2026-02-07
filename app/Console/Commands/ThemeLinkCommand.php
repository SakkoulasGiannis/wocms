<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ThemeLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:link {--force : Recreate existing symlinks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create symlinks for theme assets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themesPath = resource_path('views/themes');
        $publicPath = public_path('themes');

        // Create public/themes directory if it doesn't exist
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0755, true);
            $this->info('Created public/themes directory');
        }

        // Get all theme directories
        $themes = File::directories($themesPath);

        if (empty($themes)) {
            $this->warn('No themes found in resources/views/themes/');
            return 0;
        }

        $this->info('Creating symlinks for theme assets...');

        foreach ($themes as $themePath) {
            $themeName = basename($themePath);
            $assetsPath = $themePath . '/assets';
            $linkPath = $publicPath . '/' . $themeName . '/assets';

            // Skip if theme doesn't have assets folder
            if (!File::exists($assetsPath)) {
                $this->comment("  ⊘ {$themeName}: No assets folder");
                continue;
            }

            // Create theme directory in public
            $themePublicDir = $publicPath . '/' . $themeName;
            if (!File::exists($themePublicDir)) {
                File::makeDirectory($themePublicDir, 0755, true);
            }

            // Check if symlink already exists
            if (File::exists($linkPath)) {
                if ($this->option('force')) {
                    File::delete($linkPath);
                    $this->comment("  ↺ {$themeName}: Recreating symlink");
                } else {
                    $this->comment("  ✓ {$themeName}: Symlink already exists (use --force to recreate)");
                    continue;
                }
            }

            // Create symlink
            try {
                File::link($assetsPath, $linkPath);
                $this->info("  ✓ {$themeName}: Symlink created successfully");
            } catch (\Exception $e) {
                $this->error("  ✗ {$themeName}: Failed to create symlink - " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Theme assets linking completed!');

        return 0;
    }
}
