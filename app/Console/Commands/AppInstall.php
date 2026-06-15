<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;

/**
 * One-shot bootstrap for a fresh clone of this CMS.
 *
 * A plain `php artisan migrate` is not enough on a clean database because:
 *   1. nwidart modules default to DISABLED when `modules_statuses.json` is
 *      absent (it is gitignored), so their routes/migrations never load.
 *   2. The dynamic content tables (homes/pages/blogs) are normally created by
 *      the template engine during seeding — handled by the dedicated
 *      create_core_content_tables migration, but the templates themselves still
 *      need seeding.
 *
 * This command performs every step in the correct order and is safe to re-run.
 */
class AppInstall extends Command
{
    protected $signature = 'app:install
        {--fresh : Drop all tables first (migrate:fresh) — destroys existing data}
        {--demo  : Also seed demo users and sample blog posts}';

    protected $description = 'Bootstrap a fresh install: app key, modules, migrations, base seed (roles, admin, templates).';

    /**
     * Base seeders required for a working install (no demo content).
     */
    private const BASE_SEEDERS = [
        'RolesAndPermissionsSeeder',
        'AdminUserSeeder',
        'HomeTemplateSeeder',
        'DefaultTemplatesSeeder',
        'BlogTemplateSeeder',
        'TemplateInitSeeder',
        'SectionTemplatesSeeder',
    ];

    public function handle(): int
    {
        $this->info('▶ Installing CMS…');

        // 1. Application key
        if (empty(config('app.key'))) {
            $this->line('  • Generating application key');
            $this->call('key:generate', ['--force' => true]);
        }

        // 2. Enable modules BEFORE migrating so module migrations are discovered.
        $this->enableModules();

        // 3. Schema. The create_core_content_tables migration guarantees
        //    homes/pages/blogs exist before the blog-taxonomy FK migration runs.
        if ($this->option('fresh')) {
            $this->warn('  • migrate:fresh (dropping all tables)');
            $this->call('migrate:fresh', ['--force' => true]);
        } else {
            $this->line('  • migrate');
            $this->call('migrate', ['--force' => true]);
        }

        $this->line('  • module:migrate');
        $this->call('module:migrate', ['--force' => true]);

        // 4. Base data: roles/permissions, admin user, system templates.
        //    Templates trigger TemplateTableGenerator::syncTableColumns(), which
        //    fills in the per-template field columns on homes/pages/blogs.
        foreach (self::BASE_SEEDERS as $seeder) {
            $this->line("  • seed {$seeder}");
            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        if ($this->option('demo')) {
            $this->line('  • seed BlogPostsSeeder (demo)');
            $this->call('db:seed', ['--class' => 'BlogPostsSeeder', '--force' => true]);
        }

        // 5. Clear/rebuild caches.
        $this->line('  • optimize:clear');
        $this->call('optimize:clear');

        $this->newLine();
        $this->info('✓ Install complete. Default admin: test@example.com / password (change it!).');

        return self::SUCCESS;
    }

    /**
     * Ensure every module is enabled (writing modules_statuses.json), except the
     * ExampleBlog scaffold which ships disabled.
     */
    private function enableModules(): void
    {
        if (! class_exists(Module::class)) {
            return;
        }

        $this->line('  • Enabling modules');
        foreach (Module::all() as $name => $module) {
            if ($name === 'ExampleBlog') {
                $module->disable();

                continue;
            }
            $module->enable();
        }
    }
}
