<?php

use Illuminate\Support\Facades\Route;
use Modules\Maps\Http\Controllers\MapsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('maps', MapsController::class)->names('maps');
});
