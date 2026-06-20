<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageRevision;
use Illuminate\Console\Command;

/**
 * List revisions for a page.
 *
 *   php artisan page:revisions 5
 *   php artisan page:revisions build-your-own-villa
 *   php artisan page:revisions 5 --limit=50
 */
class PageRevisions extends Command
{
    protected $signature = 'page:revisions {identifier : Page id or slug}
                                           {--limit=20 : Max rows to show}';

    protected $description = 'List AI revision snapshots for a Page (newest first)';

    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $page = is_numeric($identifier)
            ? Page::find($identifier)
            : Page::where('slug', $identifier)->first();

        if (! $page) {
            $this->error("Page not found: {$identifier}");

            return self::FAILURE;
        }

        $rows = PageRevision::with('user:id,name,email')
            ->where('page_id', $page->id)
            ->orderByDesc('created_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($rows->isEmpty()) {
            $this->info("No revisions yet for page #{$page->id} ({$page->slug}).");

            return self::SUCCESS;
        }

        $this->info("Revisions for page #{$page->id} — {$page->title} ({$page->slug})");
        $this->newLine();

        $this->table(
            ['#', 'When', 'Source', 'By', 'Prompt (preview)'],
            $rows->map(fn ($r) => [
                $r->id,
                $r->created_at->format('Y-m-d H:i:s'),
                $r->sourceLabel(),
                $r->user?->name ?? '—',
                mb_strimwidth((string) $r->prompt, 0, 60, '…'),
            ])->all()
        );

        $this->newLine();
        $this->line('Restore with: <comment>php artisan page:restore {revision-id}</comment>');

        return self::SUCCESS;
    }
}
