<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FreshStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fresh-start {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset database, clear uploaded files and start fresh';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will DELETE ALL DATA and uploaded files. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('🚀 Starting fresh installation...');
        $this->newLine();

        // Step 1: Drop all tables and migrate fresh
        $this->info('📊 Resetting database...');
        $this->call('migrate:fresh', ['--force' => true]);
        $this->newLine();

        // Step 2: Seed database
        $this->info('🌱 Seeding database...');
        $this->call('db:seed');
        $this->newLine();

        // Step 3: Clear uploaded files and generated views
        $this->info('🗑️  Clearing uploaded files and generated views...');

        // Clear public storage files
        $publicStoragePath = public_path('storage');
        if (File::exists($publicStoragePath)) {
            $directories = ['media', 'settings', 'uploads', 'templates'];
            foreach ($directories as $dir) {
                $path = $publicStoragePath . '/' . $dir;
                if (File::exists($path)) {
                    File::deleteDirectory($path);
                    File::makeDirectory($path, 0755, true);
                    $this->comment("  ✓ Cleared: storage/{$dir}");
                }
            }
        }

        // Clear storage/app/public
        $storageDisk = Storage::disk('public');
        $directories = $storageDisk->directories();
        foreach ($directories as $directory) {
            $storageDisk->deleteDirectory($directory);
            $this->comment("  ✓ Cleared: storage/app/public/{$directory}");
        }

        // Clear frontend views (generated templates only, not default views)
        $frontendTemplatesPath = resource_path('views/frontend/templates');
        if (File::exists($frontendTemplatesPath)) {
            File::deleteDirectory($frontendTemplatesPath);
            File::makeDirectory($frontendTemplatesPath, 0755, true);
            $this->comment("  ✓ Cleared: resources/views/frontend/templates");
        }

        // Clear template views (physical template files)
        $templatesPath = resource_path('views/templates');
        if (File::exists($templatesPath)) {
            File::deleteDirectory($templatesPath);
            File::makeDirectory($templatesPath, 0755, true);
            $this->comment("  ✓ Cleared: resources/views/templates");
        }

        // Reset header and footer partials to defaults
        $partialsPath = resource_path('views/frontend/partials');
        $frontendPath = resource_path('views/frontend');

        $defaultHeader = $partialsPath . '/default-header.blade.php';
        $defaultFooter = $partialsPath . '/default-footer.blade.php';
        $defaultLayout = $frontendPath . '/default-layout.blade.php';

        $header = $partialsPath . '/header.blade.php';
        $footer = $partialsPath . '/footer.blade.php';
        $layout = $frontendPath . '/layout.blade.php';

        if (File::exists($defaultHeader)) {
            File::copy($defaultHeader, $header);
            $this->comment("  ✓ Reset: header.blade.php");
        }

        if (File::exists($defaultFooter)) {
            File::copy($defaultFooter, $footer);
            $this->comment("  ✓ Reset: footer.blade.php");
        }

        if (File::exists($defaultLayout)) {
            File::copy($defaultLayout, $layout);
            $this->comment("  ✓ Reset: layout.blade.php");
        }

        // Step 4: Clear cache
        $this->info('🧹 Clearing cache...');
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->newLine();

        // Step 5: Clear logs (optional)
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
            $this->comment('  ✓ Cleared logs');
        }

        $this->newLine();
        $this->info('✅ Fresh start completed successfully!');
        $this->newLine();
        $this->info('📝 Summary:');
        $this->line('  • Database reset and seeded');
        $this->line('  • 3 users created (Admin, Editor, User)');
        $this->line('  • Templates created: Home, Pages, Blog');
        $this->line('  • 10 blog posts with ContentNodes created');
        $this->line('  • All uploaded files deleted');
        $this->line('  • Physical template files deleted');
        $this->line('  • Layout, Header & Footer reset to defaults');
        $this->line('  • Cache cleared');
        $this->line('  • Logs cleared');
        $this->newLine();
        $this->info('🔐 Login Credentials (Password for all: password):');
        $this->line('  👤 Admin:  admin@example.com');
        $this->line('  ✏️  Editor: editor@example.com');
        $this->line('  👥 User:   user@example.com');
        $this->newLine();
        $this->info('🌐 Frontend URLs:');
        $this->line('  🏠 Home:     /');
        $this->line('  📰 Blog:     /blog (index with 10 posts)');
        $this->line('  📝 Post:     /blog/{slug}');
        $this->newLine();
        $this->info('🎯 You can now login and start fresh!');

        return 0;
    }
}
