<?php

namespace Modules\Properties\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Syncs a single rental listing from the CRM/Hostaway by its external id,
 * triggered from the per-property "Sync" button. Queued so the request never
 * blocks on the listing's media download.
 */
class SyncSingleRentalJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public string $externalId) {}

    /**
     * Only one sync of a given listing may be queued/running at a time.
     */
    public function uniqueId(): string
    {
        return 'rental-sync-'.$this->externalId;
    }

    public function uniqueFor(): int
    {
        return 600;
    }

    public function handle(): void
    {
        Log::info("SyncSingleRentalJob: starting sync (external_id={$this->externalId})");
        Artisan::call('properties:sync', ['--type' => 'rentals', '--id' => $this->externalId]);
        Log::info("SyncSingleRentalJob: finished sync (external_id={$this->externalId})");
    }
}
