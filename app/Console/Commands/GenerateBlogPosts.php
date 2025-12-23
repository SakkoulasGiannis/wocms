<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\BlogPostsSeeder;

class GenerateBlogPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:generate {count=10 : Number of blog posts to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate random blog posts with realistic content';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');

        if ($count < 1 || $count > 1000) {
            $this->error('Count must be between 1 and 1000');
            return 1;
        }

        $this->info("🚀 Generating {$count} blog posts...");
        $this->newLine();

        // Check if blogs table exists
        if (!\Schema::hasTable('blogs')) {
            $this->error('❌ Blog table does not exist!');
            $this->info('💡 Run fresh-start to create the blog template first:');
            $this->line('   php artisan fresh-start');
            return 1;
        }

        // Run the seeder
        $seeder = new BlogPostsSeeder();
        $seeder->setCommand($this);
        $seeder->setCount($count);

        $seeder->run();

        $this->newLine();
        $this->info("✅ Done! Generated {$count} blog posts.");
        $this->newLine();
        $this->info('📝 You can now view them at:');
        $this->line('   /admin/blog');

        return 0;
    }
}
