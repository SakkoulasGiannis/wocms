<?php

use Illuminate\Support\Facades\Route;
use Modules\ImageMaps\Http\Controllers\ImageMapsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('imagemaps', ImageMapsController::class)->names('imagemaps');
});
