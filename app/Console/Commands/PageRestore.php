<?php

namespace App\Console\Commands;

use App\Models\PageRevision;
use App\Services\PageCompiler;
use Illuminate\Console\Command;

/**
 * Restore a Page to a previously snapshotted revision.
 *
 *   php artisan page:restore 42         # restore revision id 42
 *   php artisan page:restore 42 --yes   # skip the confirmation prompt
 *
 * Under the hood: re-runs PageCompiler with the snapshot's spec, which means
 * the restore is itself an auditable event — a new "pre-restore" revision is
 * captured of the current state before reverting.
 */
class PageRestore extends Command
{
    protected $signature = 'page:restore {revisionId : The revision id to restore}
                                         {--yes : Skip the confirmation prompt}';

    protected $description = 'Restore a Page to a previously captured AI revision';

    public function handle(): int
    {
        $rev = PageRevision::with('page')->find($this->argument('revisionId'));

        if (! $rev) {
            $this->error('Revision not found: '.$this->argument('revisionId'));

            return self::FAILURE;
        }

        $this->info("About to restore page #{$rev->page_id} ({$rev->page?->slug}) to revision #{$rev->id}");
        $this->line("  When:   {$rev->created_at->format('Y-m-d H:i:s')}");
        $this->line("  Source: {$rev->sourceLabel()}");
        if ($rev->prompt) {
            $this->line('  Prompt: '.mb_strimwidth($rev->prompt, 0, 200, '…'));
        }

        if (! $this->option('yes') && ! $this->confirm('Proceed?', false)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        try {
            $result = PageCompiler::fromArray($rev->spec)
                ->withRevisionMeta(source: 'ai-edit', prompt: "Restored from revision #{$rev->id}")
                ->compile();
        } catch (\Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! ($result['ok'] ?? false)) {
            $this->error('Restore failed: '.($result['error'] ?? 'unknown'));

            return self::FAILURE;
        }

        $this->info('✓ Restored.');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
