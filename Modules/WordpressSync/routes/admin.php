<?php

use Modules\WordpressSync\Livewire\WpSyncDashboard;

Route::get('/wp-sync', WpSyncDashboard::class)->name('wpsync.index');
