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
 * Runs the CRM/Hostaway property sync in the background so the admin "Sync"
 * button never blocks the request or times out on a large media download.
 */
class SyncPropertiesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public string $type = 'rentals') {}

    /**
     * Only one sync of a given type may be queued/running at a time.
     */
    public function uniqueId(): string
    {
        return 'properties-sync-'.$this->type;
    }

    public function uniqueFor(): int
    {
        return 1800;
    }

    public function handle(): void
    {
        Log::info("SyncPropertiesJob: starting sync (type={$this->type})");
        Artisan::call('properties:sync', ['--type' => $this->type]);
        Log::info("SyncPropertiesJob: finished sync (type={$this->type})");
    }
}
