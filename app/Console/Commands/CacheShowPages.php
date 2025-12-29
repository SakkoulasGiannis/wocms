<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\ContentNode;

class CacheShowPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:show-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all cached pages and their status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Full Page Cache Status');
        $this->newLine();

        // Get all content nodes with caching potentially enabled
        $nodes = ContentNode::with('template')
            ->where('is_published', true)
            ->get();

        $cachedPages = [];
        $uncachedPages = [];

        foreach ($nodes as $node) {
            $cacheKey = "page.{$node->url_path}";
            $isCached = Cache::has($cacheKey);
            $cachingEnabled = $node->isCacheEnabled();

            if ($cachingEnabled) {
                if ($isCached) {
                    $cachedPages[] = [
                        'path' => $node->url_path,
                        'title' => $node->title,
                        'template' => $node->template->name ?? 'N/A',
                        'ttl' => $node->getCacheTtl() . 's',
                    ];
                } else {
                    $uncachedPages[] = [
                        'path' => $node->url_path,
                        'title' => $node->title,
                        'template' => $node->template->name ?? 'N/A',
                        'status' => 'Enabled but not cached yet',
                    ];
                }
            }
        }

        if (count($cachedPages) > 0) {
            $this->info('✅ Cached Pages (' . count($cachedPages) . ')');
            $this->table(
                ['Path', 'Title', 'Template', 'TTL'],
                $cachedPages
            );
            $this->newLine();
        }

        if (count($uncachedPages) > 0) {
            $this->warn('⏳ Caching Enabled (Not Cached Yet) (' . count($uncachedPages) . ')');
            $this->table(
                ['Path', 'Title', 'Template', 'Status'],
                $uncachedPages
            );
            $this->newLine();
        }

        if (count($cachedPages) === 0 && count($uncachedPages) === 0) {
            $this->warn('No pages have caching enabled.');
            $this->newLine();
            $this->info('To enable caching:');
            $this->line('1. Go to Admin → Templates');
            $this->line('2. Edit a template');
            $this->line('3. Enable "Full Page Caching" in Performance & Caching section');
            $this->line('4. Visit a page using that template');
        }

        // Show cache driver info
        $this->newLine();
        $this->info('Cache Driver: ' . config('cache.default'));
        $this->info('Cache Store: ' . config('cache.stores.' . config('cache.default') . '.driver'));

        return 0;
    }
}
