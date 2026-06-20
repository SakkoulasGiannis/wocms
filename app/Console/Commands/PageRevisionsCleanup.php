<?php

namespace App\Console\Commands;

use App\Models\EntityRevision;
use App\Models\PageRevision;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Delete page_revisions older than the configured retention window.
 * Scheduled daily via routes/console.php — also safe to run manually.
 *
 *   php artisan page:revisions-cleanup
 *   php artisan page:revisions-cleanup --days=14   # custom window
 *   php artisan page:revisions-cleanup --dry-run   # show count, delete nothing
 */
class PageRevisionsCleanup extends Command
{
    protected $signature = 'page:revisions-cleanup
                            {--days=30 : Keep revisions younger than this many days}
                            {--dry-run : Show counts only, do not delete}';

    protected $description = 'Delete AI page + entity revisions older than the retention window';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $totalDeleted = 0;
        foreach (['page' => PageRevision::class, 'entity' => EntityRevision::class] as $label => $model) {
            $query = $model::where('created_at', '<', $cutoff);
            $count = $query->count();

            if ($this->option('dry-run')) {
                $this->info("[dry-run] {$label}_revisions: would delete {$count} rows older than {$cutoff->toDateTimeString()}.");

                continue;
            }

            if ($count === 0) {
                $this->info("{$label}_revisions: nothing to delete older than {$cutoff->toDateTimeString()}.");

                continue;
            }

            $deleted = 0;
            $query->chunkById(1000, function ($rows) use (&$deleted, $model) {
                $ids = $rows->pluck('id')->all();
                $deleted += $model::whereIn('id', $ids)->delete();
            });

            $this->info("{$label}_revisions: deleted {$deleted} rows older than {$cutoff->toDateTimeString()}.");
            $totalDeleted += $deleted;
        }

        if (! $this->option('dry-run')) {
            $this->info("Total deleted: {$totalDeleted}");
        }

        return self::SUCCESS;
    }
}
