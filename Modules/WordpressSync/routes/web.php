<?php

use Illuminate\Support\Facades\Route;
use Modules\WordpressSync\Http\Controllers\WordpressSyncController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('wordpresssyncs', WordpressSyncController::class)->names('wordpresssync');
});
