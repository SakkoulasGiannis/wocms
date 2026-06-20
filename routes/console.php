<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily: prune AI page revisions older than 30 days to keep the table small.
Schedule::command('page:revisions-cleanup')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->onOneServer();
