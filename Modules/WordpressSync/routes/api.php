<?php

use Illuminate\Support\Facades\Route;
use Modules\WordpressSync\Http\Controllers\WordpressSyncController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('wordpresssyncs', WordpressSyncController::class)->names('wordpresssync');
});
