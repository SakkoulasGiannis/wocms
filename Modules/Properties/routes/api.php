<?php

use Illuminate\Support\Facades\Route;
use Modules\Properties\Http\Controllers\PropertiesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('properties', PropertiesController::class)->names('properties');
});
